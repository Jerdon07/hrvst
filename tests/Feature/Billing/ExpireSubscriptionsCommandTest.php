<?php

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\Profiles\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'farmer']);
});

function seedExpiryTestSubscription(array $overrides = []): Subscription
{
    $farmer = createFarmerUser();

    return Subscription::create(array_merge([
        'user_id' => $farmer->id,
        'feature' => SubscriptionFeature::FarmerForecasts,
        'plan' => SubscriptionPlan::Monthly,
        'status' => SubscriptionStatus::Active,
        'amount_cents' => 9_900,
        'currency' => 'PHP',
        'payment_gateway' => 'mock',
        'payment_reference' => 'mock_'.uniqid(),
        'starts_at' => now()->subMonth(),
        'ends_at' => now()->addDay(),
    ], $overrides));
}

it('flips an active subscription past its end date to expired', function () {
    $sub = seedExpiryTestSubscription(['ends_at' => now()->subDay()]);

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($sub->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

it('flips a cancelled subscription past its end date to expired', function () {
    $sub = seedExpiryTestSubscription([
        'status' => SubscriptionStatus::Cancelled,
        'cancelled_at' => now()->subDays(2),
        'ends_at' => now()->subDay(),
    ]);

    $this->artisan('subscriptions:expire');

    expect($sub->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

it('does not touch an active subscription that has not ended yet', function () {
    $sub = seedExpiryTestSubscription(['ends_at' => now()->addDay()]);

    $this->artisan('subscriptions:expire');

    expect($sub->fresh()->status)->toBe(SubscriptionStatus::Active);
});

it('leaves an already-expired subscription untouched (idempotent)', function () {
    $sub = seedExpiryTestSubscription(['status' => SubscriptionStatus::Expired, 'ends_at' => now()->subMonth()]);

    $this->artisan('subscriptions:expire');

    expect($sub->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

it('expires a subscription whose ends_at has just passed (boundary)', function () {
    $sub = seedExpiryTestSubscription(['ends_at' => now()->subSecond()]);

    $this->artisan('subscriptions:expire');

    expect($sub->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

it('reports the number of subscriptions it expired', function () {
    seedExpiryTestSubscription(['ends_at' => now()->subDay()]);
    seedExpiryTestSubscription(['ends_at' => now()->subDay()]);
    seedExpiryTestSubscription(['ends_at' => now()->addDay()]);

    $this->artisan('subscriptions:expire')
        ->expectsOutputToContain('Expired 2 subscription(s).');
});
