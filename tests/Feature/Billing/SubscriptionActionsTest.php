<?php

use App\Actions\Billing\CancelSubscriptionAction;
use App\Actions\Billing\SubscribeToPlanAction;
use App\Contracts\Billing\PaymentGateway;
use App\DTOs\Billing\PaymentResult;
use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Exceptions\Billing\PaymentFailedException;
use App\Models\Billing\Subscription;
use App\Models\Profiles\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

function failingPaymentGateway(): PaymentGateway
{
    return new class implements PaymentGateway
    {
        public function charge(int $amountCents, string $currency, array $meta = []): PaymentResult
        {
            return new PaymentResult(successful: false, gateway: 'mock', message: 'Card declined.');
        }
    };
}

describe('SubscribeToPlanAction', function () {
    it('creates an active subscription on successful payment', function () {
        $farmer = createFarmerUser();

        $subscription = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        );

        expect($subscription->status)->toBe(SubscriptionStatus::Active)
            ->and($subscription->user_id)->toBe($farmer->id)
            ->and($subscription->feature)->toBe(SubscriptionFeature::FarmerForecasts)
            ->and($subscription->plan)->toBe(SubscriptionPlan::Monthly);
    });

    it('sets starts_at to now and ends_at per the plan duration', function () {
        $farmer = createFarmerUser();

        $subscription = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        );

        expect($subscription->starts_at->isToday())->toBeTrue()
            ->and($subscription->ends_at->toDateString())->toBe(now()->addMonth()->toDateString());
    });

    it('expires the previous active subscription for the same feature before creating the new one', function () {
        $farmer = createFarmerUser();

        $first = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        );

        $second = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Annual
        );

        expect($first->fresh()->status)->toBe(SubscriptionStatus::Expired)
            ->and($second->status)->toBe(SubscriptionStatus::Active)
            ->and(Subscription::where('user_id', $farmer->id)->where('status', SubscriptionStatus::Active)->count())->toBe(1);
    });

    it('does not touch an active subscription under a different feature', function () {
        $dealer = createDealerUser();
        $farmer = createFarmerUser();

        $dealerSub = app(SubscribeToPlanAction::class)->handle($dealer, SubscriptionFeature::DealerMarketIntel, SubscriptionPlan::Monthly);
        app(SubscribeToPlanAction::class)->handle($farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly);
        app(SubscribeToPlanAction::class)->handle($farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Annual);

        expect($dealerSub->fresh()->status)->toBe(SubscriptionStatus::Active);
    });

    it('rolls back and creates nothing when the payment gateway declines', function () {
        $this->instance(PaymentGateway::class, failingPaymentGateway());
        $farmer = createFarmerUser();

        expect(fn () => app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        ))->toThrow(PaymentFailedException::class);

        expect(Subscription::where('user_id', $farmer->id)->count())->toBe(0);
    });

    it('does not expire the prior subscription when the new payment fails', function () {
        $farmer = createFarmerUser();
        $first = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        );

        $this->instance(PaymentGateway::class, failingPaymentGateway());

        try {
            app(SubscribeToPlanAction::class)->handle($farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Annual);
        } catch (PaymentFailedException) {
            // expected
        }

        expect($first->fresh()->status)->toBe(SubscriptionStatus::Active);
    });

    it('stores the payment gateway name and reference from the charge result', function () {
        $farmer = createFarmerUser();

        $subscription = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        );

        expect($subscription->payment_gateway)->toBe('mock')
            ->and($subscription->payment_reference)->toStartWith('mock_');
    });

    it('stores the correct amount_cents for the given feature and plan', function () {
        $farmer = createFarmerUser();

        $subscription = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        );

        expect($subscription->amount_cents)->toBe(SubscriptionPlan::Monthly->priceCents(SubscriptionFeature::FarmerForecasts));
    });
});

describe('CancelSubscriptionAction', function () {
    it('marks the active subscription cancelled and stamps cancelled_at', function () {
        $farmer = createFarmerUser();
        $subscription = app(SubscribeToPlanAction::class)->handle(
            $farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly
        );

        app(CancelSubscriptionAction::class)->handle($farmer, SubscriptionFeature::FarmerForecasts);
        $subscription->refresh();

        expect($subscription->status)->toBe(SubscriptionStatus::Cancelled)
            ->and($subscription->cancelled_at)->not->toBeNull();
    });

    it('keeps access until ends_at even after cancellation', function () {
        $farmer = createFarmerUser();
        app(SubscribeToPlanAction::class)->handle($farmer, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly);

        app(CancelSubscriptionAction::class)->handle($farmer, SubscriptionFeature::FarmerForecasts);

        expect(Subscription::hasAccess($farmer, SubscriptionFeature::FarmerForecasts))->toBeTrue();
    });

    it('does nothing when there is no active subscription for that feature', function () {
        $farmer = createFarmerUser();

        app(CancelSubscriptionAction::class)->handle($farmer, SubscriptionFeature::FarmerForecasts);

        expect(Subscription::where('user_id', $farmer->id)->count())->toBe(0);
    });

    it('does not cancel another user\'s subscription for the same feature', function () {
        $farmerA = createFarmerUser();
        $farmerB = createFarmerUser();

        $subA = app(SubscribeToPlanAction::class)->handle($farmerA, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly);
        $subB = app(SubscribeToPlanAction::class)->handle($farmerB, SubscriptionFeature::FarmerForecasts, SubscriptionPlan::Monthly);

        app(CancelSubscriptionAction::class)->handle($farmerA, SubscriptionFeature::FarmerForecasts);

        expect($subA->fresh()->status)->toBe(SubscriptionStatus::Cancelled)
            ->and($subB->fresh()->status)->toBe(SubscriptionStatus::Active);
    });
});
