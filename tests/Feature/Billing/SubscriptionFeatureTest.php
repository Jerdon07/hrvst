<?php

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\Profiles\Role;
use App\Models\User;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

function subscribeTo(User $user, SubscriptionFeature $feature, array $overrides = []): Subscription
{
    return Subscription::create(array_merge([
        'user_id' => $user->id,
        'feature' => $feature,
        'plan' => SubscriptionPlan::Monthly,
        'status' => SubscriptionStatus::Active,
        'amount_cents' => 9_900,
        'currency' => 'PHP',
        'payment_gateway' => 'mock',
        'payment_reference' => 'mock_'.uniqid(),
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ], $overrides));
}

describe('SubscriptionFeature::forUser', function () {
    it('maps admin to AdminAnalytics', function () {
        expect(SubscriptionFeature::forUser(createAdminUser()))->toBe(SubscriptionFeature::AdminAnalytics);
    });

    it('maps farmer to FarmerForecasts', function () {
        expect(SubscriptionFeature::forUser(createFarmerUser()))->toBe(SubscriptionFeature::FarmerForecasts);
    });

    it('maps dealer to DealerMarketIntel', function () {
        expect(SubscriptionFeature::forUser(createDealerUser()))->toBe(SubscriptionFeature::DealerMarketIntel);
    });

    it('returns null for a user with no recognized role', function () {
        $user = User::factory()->create();

        expect(SubscriptionFeature::forUser($user))->toBeNull();
    });
});

describe('SubscriptionFeature::hasAccessFor', function () {
    it('is true for a farmer with an active FarmerForecasts subscription', function () {
        $farmer = createFarmerUser();
        subscribeTo($farmer, SubscriptionFeature::FarmerForecasts);

        expect(SubscriptionFeature::hasAccessFor($farmer))->toBeTrue();
    });

    it('is false for a farmer with no subscription at all', function () {
        expect(SubscriptionFeature::hasAccessFor(createFarmerUser()))->toBeFalse();
    });

    it('is false for a farmer whose subscription has expired', function () {
        $farmer = createFarmerUser();
        subscribeTo($farmer, SubscriptionFeature::FarmerForecasts, [
            'status' => SubscriptionStatus::Expired,
            'ends_at' => now()->subDay(),
        ]);

        expect(SubscriptionFeature::hasAccessFor($farmer))->toBeFalse();
    });

    it('is false for a farmer whose subscription is active but past its end date', function () {
        // Regression guard: Subscription::scopeActive() checks both status
        // AND ends_at > now(). A stale "active" row past its end date must
        // not grant access — the daily ExpireSubscriptionsCommand sweep is
        // not instantaneous, so this state is reachable in production.
        $farmer = createFarmerUser();
        subscribeTo($farmer, SubscriptionFeature::FarmerForecasts, [
            'status' => SubscriptionStatus::Active,
            'ends_at' => now()->subMinute(),
        ]);

        expect(SubscriptionFeature::hasAccessFor($farmer))->toBeFalse();
    });

    it('is true for a dealer with an active DealerMarketIntel subscription', function () {
        $dealer = createDealerUser();
        subscribeTo($dealer, SubscriptionFeature::DealerMarketIntel);

        expect(SubscriptionFeature::hasAccessFor($dealer))->toBeTrue();
    });

    it('does not grant a farmer access via a dealer-tier subscription', function () {
        // A farmer subscribed under the wrong feature must not pass —
        // hasAccessFor() must check the feature forUser() derives for THIS
        // user, not any subscription row that happens to exist for them.
        $farmer = createFarmerUser();
        subscribeTo($farmer, SubscriptionFeature::DealerMarketIntel);

        expect(SubscriptionFeature::hasAccessFor($farmer))->toBeFalse();
    });

    it('is false for a user with no recognized role, without throwing', function () {
        $user = User::factory()->create();

        expect(SubscriptionFeature::hasAccessFor($user))->toBeFalse();
    });

    it('is true for an admin with an active AdminAnalytics subscription', function () {
        $admin = createAdminUser();
        subscribeTo($admin, SubscriptionFeature::AdminAnalytics);

        expect(SubscriptionFeature::hasAccessFor($admin))->toBeTrue();
    });
});
