<?php

use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\User;
use App\Models\Vegetable\Vegetable;
use Database\Seeders\AddressSeeder;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(AddressSeeder::class);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function farmerWithProfile(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('name', 'farmer')->firstOrCreate(['name' => 'farmer']));
    FarmerProfile::factory()->for($user)->create();

    return $user;
}

function validSupplyPayload(array $vegetable, ?string $scheduledDate = null): array
{
    return [
        'scheduled_date' => $scheduledDate ?? now()->addDays(3)->toDateString(),
        'time_slot' => 'morning',
        'items' => collect($vegetable)->map(fn (Vegetable $v) => [
            'vegetable_id' => $v->id,
            'quantity_kg' => 100,
        ])->all(),
    ];
}

/**
 * Creates a supply post + one item through the route so user_id is set by the
 * auth system — guarantees the policy ownership check passes in lifecycle tests.
 * Returns the created PostItem.
 */
function createSupplyViaRoute(User $farmer, Vegetable $vegetable, ?string $scheduledDate = null): PostItem
{
    actingAs($farmer)->post(route('farmer.supplies.store'), [
        'scheduled_date' => $scheduledDate ?? now()->addDays(3)->toDateString(),
        'time_slot' => 'morning',
        'items' => [['vegetable_id' => $vegetable->id, 'quantity_kg' => 100]],
    ]);

    return PostItem::latest('id')->firstOrFail();
}

// ─── Create Supply ────────────────────────────────────────────────────────────

describe('CreateSupply', function () {

    it('farmer can create a supply with items in a single request', function () {
        $farmer = farmerWithProfile();
        $vegetable1 = createVegetable();
        $vegetable2 = createVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), validSupplyPayload([$vegetable1, $vegetable2]))
            ->assertRedirect(route('farmer.supplies.index'));

        $post = Post::first();
        expect($post)->not->toBeNull()
            ->and($post->type)->toBe(PostType::Supply)
            ->and($post->time_slot->value)->toBe('morning')
            ->and($post->postItems)->toHaveCount(2);

        expect((float) $post->postItems->firstWhere('vegetable_id', $vegetable1->id)->quantity_kg)->toBe(100.0);
    });

    it('returns other posters for the same vegetable in the same slot', function () {
        $owner = farmerWithProfile();
        $other = farmerWithProfile();
        $vegetable = createVegetable();
        $date = now()->addDays(4)->toDateString();

        $ownerPost = createSupplyPost($owner, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);

        createSupplyPost($other, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);

        $overlap = app(App\Services\Post\PostScheduleOverlapService::class)->forPost($ownerPost);
        $itemOverlap = $overlap[$ownerPost->postItems->first()->id] ?? null;

        expect($itemOverlap)->not->toBeNull()
            ->and($itemOverlap->posters)->toHaveCount(1)
            ->and($itemOverlap->posters[0]->poster_name)->toBe($other->name);
    });

    it('creation is atomic — no post exists if items fail', function () {
        $farmer = farmerWithProfile();
        $vegetable = createVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'vegetable_id' => $vegetable->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [['vegetable_id' => 99999, 'quantity_kg' => 10]],
            ])
            ->assertSessionHasErrors('items.0.vegetable_id');

        expect(Post::count())->toBe(0)
            ->and(PostItem::count())->toBe(0);
    });

    it('rejects a past scheduled_date', function () {
        $farmer = farmerWithProfile();
        $vegetable = createVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), validSupplyPayload([$vegetable], now()->subDay()->toDateString()))
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects scheduled_date beyond 3 months', function () {
        $farmer = farmerWithProfile();
        $vegetable = createVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), validSupplyPayload([$vegetable], now()->addMonths(4)->toDateString()))
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects missing required fields', function () {
        $farmer = farmerWithProfile();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [])
            ->assertSessionHasErrors(['scheduled_date', 'time_slot', 'items']);
    });

    it('rejects an empty items array', function () {
        $farmer = farmerWithProfile();
        $vegetable = createVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    });

    it('rejects item with zero quantity', function () {
        $farmer = farmerWithProfile();
        $vegetable = createVegetable();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), [
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [
                    ['vegetable_id' => $vegetable->id, 'quantity_kg' => 0],
                ],
            ])
            ->assertSessionHasErrors('items.0.quantity_kg');
    });

    it('non-farmer cannot create a supply', function () {
        $dealer = User::factory()->create();
        $dealer->roles()->attach(Role::where('name', 'dealer')->firstOrCreate(['name' => 'dealer']));
        $vegetable = createVegetable();

        actingAs($dealer)
            ->post(route('farmer.supplies.store'), validSupplyPayload([$vegetable]))
            ->assertForbidden();
    });
});

// ─── Update Supply ────────────────────────────────────────────────────────────

describe('UpdateSupply', function () {

    it('farmer can update scheduled_date', function () {
        $farmer = farmerWithProfile();
        $vegetable = createVegetable();
        $item = createSupplyViaRoute($farmer, $vegetable);
        $post = $item->post;

        $newDate = now()->addDays(10)->toDateString();

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), ['scheduled_date' => $newDate])
            ->assertRedirect(route('farmer.supplies.index'));

        expect($post->fresh()->scheduled_date->toDateString())->toBe($newDate);
    });

    it('farmer cannot update another farmer\'s supply', function () {
        $farmer = farmerWithProfile();
        $other = farmerWithProfile();
        $item = createSupplyViaRoute($other, createVegetable());
        $post = $item->post;

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), [
                'scheduled_date' => now()->addDays(5)->toDateString(),
            ])
            ->assertForbidden();
    });

    it('updating quantity on an overdue supply does not fail because scheduled_date is unchanged', function () {
        $dealer = farmerWithProfile();
        $vegetable = createVegetable();
        $item = createSupplyViaRoute($dealer, $vegetable);
        $post = $item->post;
        $post->update(['scheduled_date' => now()->subDay()->toDateString()]); // force overdue

        actingAs($dealer)
            ->put(route('farmer.supplies.update', $post), [
                'scheduled_date' => $post->scheduled_date->format('Y-m-d'),
                'items' => [['vegetable_id' => $vegetable->id, 'quantity_kg' => 75]],
            ])
            ->assertSessionHasNoErrors();
    });

});

// ─── Supply Lifecycle ─────────────────────────────────────────────────────────

describe('SupplyLifecycle', function () {

    it('farmer can delete a supply', function () {
        $farmer = farmerWithProfile();
        $item = createSupplyViaRoute($farmer, createVegetable());
        $post = $item->post;

        actingAs($farmer)
            ->delete(route('farmer.supplies.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull();
    });

    it('deleting a supply soft-deletes the post record', function () {
        $farmer = farmerWithProfile();
        $item = createSupplyViaRoute($farmer, createVegetable());
        $post = $item->post;

        actingAs($farmer)
            ->delete(route('farmer.supplies.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull()
            ->and(Post::withTrashed()->find($post->id))->not->toBeNull();
    });
});

// ─── Cross-role access ────────────────────────────────────────────────────────

describe('CrossRoleAccess', function () {

    it('dealer cannot access farmer supply routes', function () {
        $dealer = User::factory()->create();
        $dealer->roles()->attach(Role::firstOrCreate(['name' => 'dealer']));

        actingAs($dealer)
            ->post(route('farmer.supplies.store'), [])
            ->assertForbidden();
    });

    it('unauthenticated user is redirected from supply and demand routes', function () {
        $this->post(route('dealer.demands.store'), [])->assertRedirect(route('login'));
        $this->post(route('farmer.supplies.store'), [])->assertRedirect(route('login'));
    });

});
