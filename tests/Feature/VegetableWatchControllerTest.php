<?php

use App\Models\Vegetable\VegetableWatch;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

describe('store', function () {
    it('lets a farmer watch a vegetable and records their role', function () {
        $farmer = createFarmerUser();
        $vegetable = createVegetable();

        actingAs($farmer)
            ->post(route('vegetables.watch', $vegetable))
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect(VegetableWatch::where('user_id', $farmer->id)->where('vegetable_id', $vegetable->id)->first()->viewer_role)
            ->toBe('farmer');
    });

    it('lets a dealer watch a vegetable and records their role', function () {
        $dealer = createDealerUser();

        actingAs($dealer)->post(route('vegetables.watch', createVegetable()));

        expect(VegetableWatch::where('user_id', $dealer->id)->first()->viewer_role)->toBe('dealer');
    });

    it('is idempotent — watching twice does not create a duplicate row', function () {
        $farmer = createFarmerUser();
        $vegetable = createVegetable();

        actingAs($farmer)->post(route('vegetables.watch', $vegetable));
        actingAs($farmer)->post(route('vegetables.watch', $vegetable));

        expect(VegetableWatch::where('user_id', $farmer->id)->where('vegetable_id', $vegetable->id)->count())->toBe(1);
    });

    it('denies an admin from watching a vegetable', function () {
        actingAs(createAdminUser())
            ->post(route('vegetables.watch', createVegetable()))
            ->assertForbidden();
    });

    it('redirects a guest to login', function () {
        post(route('vegetables.watch', createVegetable()))->assertRedirect(route('login'));
    });
});

describe('destroy', function () {
    it('lets the owning user unwatch', function () {
        $farmer = createFarmerUser();
        $vegetable = createVegetable();
        VegetableWatch::create(['user_id' => $farmer->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer']);

        actingAs($farmer)
            ->delete(route('vegetables.unwatch', $vegetable))
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect(VegetableWatch::where('user_id', $farmer->id)->where('vegetable_id', $vegetable->id)->exists())->toBeFalse();
    });

    it('404s when the user is not watching that vegetable', function () {
        actingAs(createFarmerUser())
            ->delete(route('vegetables.unwatch', createVegetable()))
            ->assertNotFound();
    });

    it('denies unwatching another user\'s watch — lookup is scoped to the authenticated user', function () {
        $owner = createFarmerUser();
        $intruder = createFarmerUser();
        $vegetable = createVegetable();
        VegetableWatch::create(['user_id' => $owner->id, 'vegetable_id' => $vegetable->id, 'viewer_role' => 'farmer']);

        actingAs($intruder)
            ->delete(route('vegetables.unwatch', $vegetable))
            ->assertNotFound();

        expect(VegetableWatch::where('user_id', $owner->id)->exists())->toBeTrue();
    });
});

describe('index', function () {
    it('renders the watches page for the authenticated user', function () {
        $farmer = createFarmerUser();
        VegetableWatch::create(['user_id' => $farmer->id, 'vegetable_id' => createVegetable()->id, 'viewer_role' => 'farmer']);

        actingAs($farmer)
            ->get(route('watches.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('shared/vegetables/Watches'));
    });
});
