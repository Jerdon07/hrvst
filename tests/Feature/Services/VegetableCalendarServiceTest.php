<?php

use App\Enums\Post\PostTimeSlot;
use App\Services\Vegetable\VegetableCalendarService;

beforeEach(function () {
    $this->service = app(VegetableCalendarService::class);
});

it('aggregates supply and demand for the same date and slot into net_kg', function () {
    $vegetable = createVegetable();
    $date = now()->addDays(5);

    createSupplyPost(createFarmerUser(), $vegetable, ['scheduled_date' => $date->toDateString(), 'time_slot' => PostTimeSlot::Morning]);
    createDemandPost(createDealerUser(), $vegetable, ['scheduled_date' => $date->toDateString(), 'time_slot' => PostTimeSlot::Morning]);

    $slot = $this->service->buildForMonth($vegetable->id, $date->year, $date->month)[$date->toDateString()]['morning'];

    expect($slot['supply_kg'])->toBe(100.0)
        ->and($slot['demand_kg'])->toBe(50.0)
        ->and($slot['net_kg'])->toBe(50.0);
});

it('lists individual items alongside the slot aggregate', function () {
    $vegetable = createVegetable();
    $date = now()->addDays(5);

    createSupplyPost(createFarmerUser(), $vegetable, ['scheduled_date' => $date->toDateString(), 'time_slot' => PostTimeSlot::Morning]);

    $schedule = $this->service->buildForMonth($vegetable->id, $date->year, $date->month);

    expect($schedule[$date->toDateString()]['morning']['items'])->toHaveCount(1);
});

it('separates different time slots on the same date', function () {
    $vegetable = createVegetable();
    $date = now()->addDays(5);

    createSupplyPost(createFarmerUser(), $vegetable, ['scheduled_date' => $date->toDateString(), 'time_slot' => PostTimeSlot::Morning]);
    createSupplyPost(createFarmerUser(), $vegetable, ['scheduled_date' => $date->toDateString(), 'time_slot' => PostTimeSlot::Evening]);

    $schedule = $this->service->buildForMonth($vegetable->id, $date->year, $date->month);

    expect($schedule[$date->toDateString()])->toHaveKeys(['morning', 'evening'])
        ->and($schedule[$date->toDateString()]['morning']['supply_kg'])->toBe(100.0)
        ->and($schedule[$date->toDateString()]['evening']['supply_kg'])->toBe(100.0);
});

it('excludes posts outside the requested month', function () {
    $vegetable = createVegetable();
    $inMonth = now()->addDays(5);
    $nextMonth = now()->addDays(5)->addMonthNoOverflow();

    createSupplyPost(createFarmerUser(), $vegetable, ['scheduled_date' => $nextMonth->toDateString(), 'time_slot' => PostTimeSlot::Morning]);

    $schedule = $this->service->buildForMonth($vegetable->id, $inMonth->year, $inMonth->month);

    expect($schedule)->not->toHaveKey($nextMonth->toDateString());
});

it('excludes a soft-deleted post from the schedule', function () {
    $vegetable = createVegetable();
    $date = now()->addDays(5);

    $post = createSupplyPost(createFarmerUser(), $vegetable, ['scheduled_date' => $date->toDateString(), 'time_slot' => PostTimeSlot::Morning]);
    $post->delete();

    expect($this->service->buildForMonth($vegetable->id, $date->year, $date->month))->not->toHaveKey($date->toDateString());
});

it('excludes a different vegetable\'s posts', function () {
    $vegetable = createVegetable();
    $other = createVegetable();
    $date = now()->addDays(5);

    createSupplyPost(createFarmerUser(), $other, ['scheduled_date' => $date->toDateString(), 'time_slot' => PostTimeSlot::Morning]);

    expect($this->service->buildForMonth($vegetable->id, $date->year, $date->month))->toBeEmpty();
});
