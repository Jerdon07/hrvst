<?php

use App\Enums\PostItemStatus;
use App\Enums\PostTimeSlot;
use App\Models\User;
use App\Models\Vegetable\Vegetable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function overlapDate(): string
{
    return now()->addDays(4)->toDateString();
}

/** @return array{scheduled_date: string, time_slot: PostTimeSlot} */
function overlapSlot(): array
{
    return ['scheduled_date' => overlapDate(), 'time_slot' => PostTimeSlot::Morning];
}

function overlapUrl(array $params = []): string
{
    return route('api.posts.overlap', array_merge([
        'type' => 'supply',
        'scheduled_date' => overlapDate(),
        'time_slot' => 'morning',
    ], $params));
}

/**
 * Requests the preview as $user and returns the decoded payload, keyed by
 * vegetable id — the exact shape useOverlapPreview / ScheduleItemsEditor read.
 *
 * @param  array<int, Vegetable>  $vegetables
 */
function fetchOverlapPreview(User $user, array $vegetables, array $params = []): array
{
    $response = actingAs($user)->getJson(overlapUrl(array_merge([
        'vegetable_ids' => collect($vegetables)->pluck('id')->all(),
    ], $params)));

    $response->assertOk();

    return $response->json();
}

// ─── Access & validation ──────────────────────────────────────────────────────

describe('access and validation', function () {
    it('rejects guests', function () {
        getJson(overlapUrl(['vegetable_ids' => [1]]))->assertUnauthorized();
    });

    it('forbids a dealer from previewing supply overlap', function () {
        $vegetable = createVegetable();

        actingAs(createDealerUser())
            ->getJson(overlapUrl(['vegetable_ids' => [$vegetable->id]]))
            ->assertForbidden();
    });

    it('forbids previewing against another user\'s post', function () {
        $vegetable = createVegetable();
        $post = createSupplyPost(createFarmerUser(), $vegetable, overlapSlot());

        actingAs(createFarmerUser())
            ->getJson(overlapUrl(['post_id' => $post->id, 'vegetable_ids' => [$vegetable->id]]))
            ->assertForbidden();
    });

    it('rejects a post_id whose type does not match the requested type', function () {
        $vegetable = createVegetable();
        $demand = createDemandPost(createDealerUser(), $vegetable, overlapSlot());

        actingAs(createFarmerUser())
            ->getJson(overlapUrl(['post_id' => $demand->id, 'vegetable_ids' => [$vegetable->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('post_id');
    });

    it('rejects an unknown vegetable id', function () {
        actingAs(createFarmerUser())
            ->getJson(overlapUrl(['vegetable_ids' => [999999]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vegetable_ids.0');
    });

    it('rejects an invalid type', function () {
        $vegetable = createVegetable();

        actingAs(createFarmerUser())
            ->getJson(overlapUrl(['type' => 'nonsense', 'vegetable_ids' => [$vegetable->id]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    });

    it('returns an empty payload until both date and slot are chosen', function () {
        $vegetable = createVegetable();
        $farmer = createFarmerUser();

        actingAs($farmer)
            ->getJson(overlapUrl(['scheduled_date' => null, 'vegetable_ids' => [$vegetable->id]]))
            ->assertOk()
            ->assertExactJson([]);

        actingAs($farmer)
            ->getJson(overlapUrl(['time_slot' => null, 'vegetable_ids' => [$vegetable->id]]))
            ->assertOk()
            ->assertExactJson([]);
    });
});

// ─── Payload ──────────────────────────────────────────────────────────────────

describe('payload', function () {
    it('lists other posters, excludes the requester, and exposes totals the UI derives net kg from', function () {
        $farmer = createFarmerUser();
        $otherFarmer = createFarmerUser();
        $dealer = createDealerUser();
        $vegetable = createVegetable();

        createSupplyPost($farmer, $vegetable, overlapSlot());                       // requester's own — excluded
        $otherSupply = createSupplyPost($otherFarmer, $vegetable, overlapSlot());   // 100 kg
        createDemandPost($dealer, $vegetable, overlapSlot());                       // 50 kg

        $entry = fetchOverlapPreview($farmer, [$vegetable])[$vegetable->id];

        expect($entry['posters'])->toHaveCount(2)
            ->and($entry['supply_posters'])->toHaveCount(1)
            ->and($entry['supply_posters'][0]['poster_id'])->toBe($otherFarmer->id)
            ->and($entry['supply_posters'][0]['post_item_id'])->toBe($otherSupply->postItems()->value('id'))
            ->and($entry['demand_posters'])->toHaveCount(1)
            ->and($entry['demand_posters'][0]['poster_id'])->toBe($dealer->id)
            ->and($entry['total_supplies_kg'])->toEqual(100)
            ->and($entry['total_demands_kg'])->toEqual(50);

        // ScheduleItemsEditor computes net kg as supplies - demands from this payload.
        expect($entry['total_supplies_kg'] - $entry['total_demands_kg'])->toEqual(50);
    });

    it('gives every poster row a distinct post_item_id even when one user posts twice', function () {
        $farmer = createFarmerUser();
        $otherFarmer = createFarmerUser();
        $vegetable = createVegetable();

        createSupplyPost($otherFarmer, $vegetable, overlapSlot());
        createSupplyPost($otherFarmer, $vegetable, overlapSlot());

        $posters = fetchOverlapPreview($farmer, [$vegetable])[$vegetable->id]['supply_posters'];
        $ids = array_column($posters, 'post_item_id');

        expect($posters)->toHaveCount(2)
            ->and(array_unique(array_column($posters, 'poster_id')))->toHaveCount(1)
            ->and(array_unique($ids))->toHaveCount(2);
    });

    it('ignores items that are no longer ongoing', function () {
        $farmer = createFarmerUser();
        $vegetable = createVegetable();

        $fulfilled = createSupplyPost(createFarmerUser(), $vegetable, overlapSlot());
        $fulfilled->postItems()->update(['status' => PostItemStatus::Fulfilled]);

        $expired = createDemandPost(createDealerUser(), $vegetable, overlapSlot());
        $expired->postItems()->update(['status' => PostItemStatus::Expired]);

        $entry = fetchOverlapPreview($farmer, [$vegetable])[$vegetable->id];

        expect($entry['posters'])->toBeEmpty()
            ->and($entry['total_supplies_kg'])->toEqual(0)
            ->and($entry['total_demands_kg'])->toEqual(0);
    });

    it('is scoped to the requested date, slot and vegetable', function () {
        $farmer = createFarmerUser();
        $other = createFarmerUser();
        $vegetable = createVegetable();
        $otherVegetable = createVegetable();

        createSupplyPost($other, $vegetable, ['scheduled_date' => now()->addDays(9)->toDateString(), 'time_slot' => PostTimeSlot::Morning]);
        createSupplyPost($other, $vegetable, ['scheduled_date' => overlapDate(), 'time_slot' => PostTimeSlot::Evening]);
        createSupplyPost($other, $otherVegetable, overlapSlot());

        $entry = fetchOverlapPreview($farmer, [$vegetable])[$vegetable->id];

        expect($entry['posters'])->toBeEmpty();
    });

    it('returns an entry for every requested vegetable, including ones with no overlap', function () {
        // The UI shows "Balanced" from a zeroed entry; a missing key would render nothing.
        $farmer = createFarmerUser();
        $crowded = createVegetable();
        $quiet = createVegetable();

        createSupplyPost(createFarmerUser(), $crowded, overlapSlot());

        $data = fetchOverlapPreview($farmer, [$crowded, $quiet]);

        expect(array_keys($data))->toEqualCanonicalizing([$crowded->id, $quiet->id])
            ->and($data[$quiet->id]['posters'])->toBeEmpty()
            ->and($data[$quiet->id]['total_supplies_kg'])->toEqual(0)
            ->and($data[$quiet->id]['total_demands_kg'])->toEqual(0);
    });

    it('serves the dealer side: a demand preview counts farmers\' supply and excludes the dealer\'s own demand', function () {
        $dealer = createDealerUser();
        $vegetable = createVegetable();

        createDemandPost($dealer, $vegetable, overlapSlot());
        createSupplyPost(createFarmerUser(), $vegetable, overlapSlot());

        $entry = fetchOverlapPreview($dealer, [$vegetable], ['type' => 'demand'])[$vegetable->id];

        expect($entry['supply_posters'])->toHaveCount(1)
            ->and($entry['demand_posters'])->toBeEmpty()
            ->and($entry['total_supplies_kg'])->toEqual(100)
            ->and($entry['total_demands_kg'])->toEqual(0);
    });
});

// ─── Edit mode ────────────────────────────────────────────────────────────────

describe('edit mode', function () {
    it('accepts the edited post id, reports its own item id, and still excludes its own rows', function () {
        $farmer = createFarmerUser();
        $vegetable = createVegetable();

        $own = createSupplyPost($farmer, $vegetable, overlapSlot());
        createSupplyPost(createFarmerUser(), $vegetable, overlapSlot());

        $entry = fetchOverlapPreview($farmer, [$vegetable], ['post_id' => $own->id])[$vegetable->id];

        expect($entry['post_item_id'])->toBe($own->postItems()->value('id'))
            ->and($entry['supply_posters'])->toHaveCount(1)
            ->and($entry['total_supplies_kg'])->toEqual(100);
    });

    it('reports post_item_id 0 for a vegetable not yet on the post (create mode)', function () {
        $farmer = createFarmerUser();
        $vegetable = createVegetable();

        $entry = fetchOverlapPreview($farmer, [$vegetable])[$vegetable->id];

        expect($entry['post_item_id'])->toBe(0);
    });
});
