<?php

use App\Enums\Post\PostTimeSlot;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects guests to login', function () {
    get(route('market.index'))->assertRedirect(route('login'));
});

it('renders for every role, admin included', function (string $factory) {
    actingAs($factory())
        ->get(route('market.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shared/market/Index')
            ->where('filters.date', today()->toDateString())
            ->where('filters.slot', null));
})->with([
    'farmer' => fn () => 'createFarmerUser',
    'dealer' => fn () => 'createDealerUser',
    'admin' => fn () => 'createAdminUser',
]);

it('rejects a past date and an unknown slot', function () {
    actingAs(createFarmerUser())
        ->get(route('market.index', ['date' => now()->subDay()->toDateString(), 'slot' => 'midnight']))
        ->assertSessionHasErrors(['date', 'slot']);
});

it('rejects a date beyond three months', function () {
    actingAs(createFarmerUser())
        ->get(route('market.index', ['date' => now()->addMonths(4)->toDateString()]))
        ->assertSessionHasErrors('date');
});

it('serves rows through the deferred prop', function () {
    $farmer = createFarmerUser();
    $vegetable = createVegetable();
    $date = now()->addDays(2)->toDateString();
    createSupplyPost($farmer, $vegetable, ['scheduled_date' => $date, 'time_slot' => PostTimeSlot::Morning]);

    actingAs($farmer);
    $rows = getDeferredProp(route('market.index', ['date' => $date]), 'shared/market/Index', 'rows');

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['supply_kg'])->toEqual(100);
});