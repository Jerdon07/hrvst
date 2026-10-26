<?php

use App\Enums\Post\PostTimeSlot;
use App\Enums\PostItemStatus;
use App\Services\Vegetable\DailyMarketService;

beforeEach(function () {
    $this->service = app(DailyMarketService::class);
    $this->date = now()->addDays(3);
});

function marketSlot(object $test, PostTimeSlot $slot = PostTimeSlot::Morning): array
{
    return ['scheduled_date' => $test->date->toDateString(), 'time_slot' => $slot];
}

it('aggregates supply and demand per vegetable', function () {
    $vegetable = createVegetable();
    createSupplyPost(createFarmerUser(), $vegetable, marketSlot($this));
    createSupplyPost(createFarmerUser(), $vegetable, marketSlot($this));
    createDemandPost(createDealerUser(), $vegetable, marketSlot($this));

    $row = $this->service->forDay($this->date)[0];

    expect($row['vegetable_id'])->toBe($vegetable->id)
        ->and($row['supply_kg'])->toBe(200.0)
        ->and($row['demand_kg'])->toBe(50.0)
        ->and($row['net_kg'])->toBe(150.0)
        ->and($row['supply_posts'])->toBe(2)
        ->and($row['demand_posts'])->toBe(1);
});

it('includes every slot when slot is null and narrows when set', function () {
    $vegetable = createVegetable();
    createSupplyPost(createFarmerUser(), $vegetable, marketSlot($this, PostTimeSlot::Morning));
    createSupplyPost(createFarmerUser(), $vegetable, marketSlot($this, PostTimeSlot::Evening));

    expect($this->service->forDay($this->date)[0]['supply_kg'])->toBe(200.0)
        ->and($this->service->forDay($this->date, PostTimeSlot::Evening)[0]['supply_kg'])->toBe(100.0)
        ->and($this->service->forDay($this->date, PostTimeSlot::Afternoon))->toBeEmpty();
});

it('excludes fulfilled, expired, soft-deleted and other-day items', function () {
    $vegetable = createVegetable();

    foreach ([PostItemStatus::Fulfilled, PostItemStatus::Expired] as $status) {
        createSupplyPost(createFarmerUser(), $vegetable, marketSlot($this))
            ->postItems()->update(['status' => $status]);
    }

    createSupplyPost(createFarmerUser(), $vegetable, marketSlot($this))->delete();
    createSupplyPost(createFarmerUser(), $vegetable, ['scheduled_date' => $this->date->copy()->addDay()->toDateString()]);

    expect($this->service->forDay($this->date))->toBeEmpty();
});

it('sorts by absolute net imbalance, largest first', function () {
    [$small, $glut, $shortage] = [createVegetable(), createVegetable(), createVegetable()];

    createSupplyPost(createFarmerUser(), $small, marketSlot($this));
    createSupplyPost(createFarmerUser(), $glut, marketSlot($this));
    createSupplyPost(createFarmerUser(), $glut, marketSlot($this));
    createDemandPost(createDealerUser(), $shortage, marketSlot($this));

    $ids = collect($this->service->forDay($this->date))->pluck('vegetable_id')->all();

    expect($ids)->toBe([$glut->id, $small->id, $shortage->id]);
});