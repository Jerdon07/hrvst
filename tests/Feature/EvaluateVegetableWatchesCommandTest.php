<?php

use App\Enums\Analytics\ImbalanceBand;
use App\Models\Vegetable\VegetableWatch;
use App\Notifications\VegetableOutlookAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * No posts are created in this file, so PlatformActivityService::monthlyActiveCounts()
 * returns an empty collection — every month's active-farmer/dealer count falls back to
 * 1, matching the mocked-empty PlatformActivityService used in SeasonalForecasterTest.
 * The 150/100 supply/demand split is chosen so the ratio (0.5) sits well clear of the
 * 0.20 oversupply threshold, avoiding hysteresis-margin flakiness at the boundary.
 */
function seedOversupplyHistory($vegetable, int $months = 24): void
{
    for ($i = $months; $i >= 1; $i--) {
        DB::table('vegetable_monthly_stats')->insert([
            'vegetable_id' => $vegetable->id,
            'period_date' => now()->startOfMonth()->subMonths($i)->toDateString(),
            'supply_fulfilled_kg' => 150, 'supply_expired_kg' => 0,
            'demand_fulfilled_kg' => 100, 'demand_expired_kg' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}

it('does not notify when history is too short for a confident forecast', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $vegetable = createVegetable();

    for ($i = 3; $i >= 1; $i--) {
        DB::table('vegetable_monthly_stats')->insert([
            'vegetable_id' => $vegetable->id,
            'period_date' => now()->startOfMonth()->subMonths($i)->toDateString(),
            'supply_fulfilled_kg' => 150, 'supply_expired_kg' => 0,
            'demand_fulfilled_kg' => 100, 'demand_expired_kg' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    VegetableWatch::create(['user_id' => $farmer->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer']);

    $this->artisan('vegetable-watches:evaluate')->assertSuccessful();

    Notification::assertNothingSent();
});

it('notifies a watcher the first time a sustained oversupply outlook is detected', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $vegetable = createVegetable();
    seedOversupplyHistory($vegetable);

    $watch = VegetableWatch::create(['user_id' => $farmer->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer']);

    $this->artisan('vegetable-watches:evaluate')->assertSuccessful();

    Notification::assertSentTo($farmer, VegetableOutlookAlert::class);
    expect($watch->fresh()->last_notified_band)->toBe(ImbalanceBand::Oversupply->value)
        ->and($watch->fresh()->last_evaluated_at)->not->toBeNull();
});

it('does not re-notify when the band has not changed since the last notification', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $vegetable = createVegetable();
    seedOversupplyHistory($vegetable);

    VegetableWatch::create([
        'user_id' => $farmer->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer',
        'last_notified_band' => ImbalanceBand::Oversupply->value,
    ]);

    $this->artisan('vegetable-watches:evaluate');

    Notification::assertNothingSent();
});

it('still updates last_evaluated_at even when it skips notifying', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $vegetable = createVegetable();
    seedOversupplyHistory($vegetable);

    $watch = VegetableWatch::create([
        'user_id' => $farmer->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer',
        'last_notified_band' => ImbalanceBand::Oversupply->value,
    ]);

    $this->artisan('vegetable-watches:evaluate');

    expect($watch->fresh()->last_evaluated_at)->not->toBeNull();
});

it('notifies every distinct watcher of the same vegetable', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $dealer = createDealerUser();
    $vegetable = createVegetable();
    seedOversupplyHistory($vegetable);

    VegetableWatch::create(['user_id' => $farmer->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer']);
    VegetableWatch::create(['user_id' => $dealer->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'dealer']);

    $this->artisan('vegetable-watches:evaluate');

    Notification::assertSentTo($farmer, VegetableOutlookAlert::class);
    Notification::assertSentTo($dealer, VegetableOutlookAlert::class);
});

it('reports how many outlook alerts it sent', function () {
    Notification::fake();

    $vegetable = createVegetable();
    seedOversupplyHistory($vegetable);

    VegetableWatch::create(['user_id' => createFarmerUser()->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer']);

    $this->artisan('vegetable-watches:evaluate')
        ->expectsOutputToContain('Sent 1 outlook alert(s).');
});
