<?php

use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\Role;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\User;
use App\Models\Vegetable\Vegetable;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Storage::fake('public');
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function dealerWithProfile(): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_pin' => false,
    ]);
    $user->roles()->attach(Role::firstOrCreate(['name' => 'dealer']));
    DealerProfile::create(['user_id' => $user->id]);

    return $user;
}

function validDemandPayload(array $vegetable, ?string $scheduledDate = null): array
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
 * Creates a demand post + one item through the route so user_id is set by the
 * auth system — guarantees the policy ownership check passes in lifecycle tests.
 * Returns the created PostItem.
 */
function createDemandViaRoute(User $dealer, Vegetable $vegetable): PostItem
{
    actingAs($dealer)->post(route('dealer.demands.store'), [
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'time_slot' => 'morning',
        'items' => [
            ['vegetable_id' => $vegetable->id, 'quantity_kg' => 50],
        ],
    ]);

    return PostItem::latest('id')->firstOrFail();
}

// ─── Create Demand ────────────────────────────────────────────────────────────

describe('CreateDemand', function () {

    it('dealer can create a demand with items in a single request', function () {
        $dealer = dealerWithProfile();
        $vegetable1 = createVegetable();
        $vegetable2 = createVegetable();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload([$vegetable1, $vegetable2]))
            ->assertRedirect(route('dealer.demands.index'));

        $post = Post::first();
        expect($post)->not->toBeNull()
            ->and($post->type)->toBe(PostType::Demand)
            ->and($post->time_slot->value)->toBe('morning')
            ->and($post->postItems)->toHaveCount(2);

        expect((float) $post->postItems->firstWhere('vegetable_id', $vegetable1->id)->quantity_kg)->toBe(100.0);
    });

    it('demand creation is atomic — no post exists if items fail', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [
                    ['vegetable_id' => 99999, 'quantity_kg' => 10],
                ],
            ])
            ->assertSessionHasErrors('items.0.vegetable_id');

        expect(Post::count())->toBe(0)
            ->and(PostItem::count())->toBe(0);
    });

    it('rejects past scheduled_date', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload([$vegetable], now()->subDay()->toDateString()))
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects scheduled_date beyond 3 months', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), validDemandPayload([$vegetable], now()->addMonths(4)->toDateString()))
            ->assertSessionHasErrors('scheduled_date');
    });

    it('rejects missing required fields', function () {
        $dealer = dealerWithProfile();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [])
            ->assertSessionHasErrors(['scheduled_date', 'time_slot', 'items']);
    });

    it('rejects empty items array', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    });

    it('rejects item with zero quantity', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();

        actingAs($dealer)
            ->post(route('dealer.demands.store'), [
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
                'items' => [
                    ['vegetable_id' => $vegetable->id, 'quantity_kg' => 0],
                ],
            ])
            ->assertSessionHasErrors('items.0.quantity_kg');
    });

    it('non-dealer cannot create a demand', function () {
        $user = User::factory()->create();
        $vegetable = createVegetable();

        actingAs($user)
            ->post(route('dealer.demands.store'), validDemandPayload([$vegetable]))
            ->assertForbidden();
    });
});

// ─── Update Demand ────────────────────────────────────────────────────────────

describe('UpdateDemand', function () {

    it('dealer can update scheduled_date on an ongoing demand', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();
        $item = createDemandViaRoute($dealer, $vegetable);
        $post = $item->post;

        $newDate = now()->addDays(10)->toDateString();

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_date' => $newDate])
            ->assertRedirect(route('dealer.demands.show', $post));

        expect($post->fresh()->scheduled_date->toDateString())->toBe($newDate);
    });

    // Regression test. HandlesPostSchedule::update() used to type-hint the
    // abstract UpdatePostRequest directly — the container can't instantiate
    // it (BindingResolutionException, 500 in prod). DemandController now
    // overrides update() with the concrete UpdateDemandRequest, same as it
    // already did for store(). Asserting the concrete class's validation
    // rules actually fire is proof the right class is being resolved, not
    // just that *a* class resolves.
    it('resolves the concrete UpdateDemandRequest and enforces its validation rules', function () {
        $dealer = dealerWithProfile();
        $item = createDemandViaRoute($dealer, createVegetable());
        $post = $item->post;

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), [
                'time_slot' => 'not-a-real-slot',
            ])
            ->assertSessionHasErrors('time_slot');
    });

    it('dealer cannot update post that is not ongoing', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();
        $item = createDemandViaRoute($dealer, $vegetable);
        $item->update(['status' => PostItemStatus::Expired]);
        $post = $item->post;

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), [
                'scheduled_date' => now()->addDay(5)->toDateString(),
            ])
            ->assertForbidden();
    });

    it('dealer cannot update another dealer\'s demand', function () {
        $dealer = dealerWithProfile();
        $other = dealerWithProfile();
        $item = createDemandViaRoute($other, createVegetable());
        $post = $item->post;

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), ['scheduled_date' => now()->addDays(2)->toDateString()])
            ->assertForbidden();
    });

    it('updating quantity on an overdue demand does not fail because scheduled_date is unchanged', function () {
        $dealer = dealerWithProfile();
        $vegetable = createVegetable();
        $item = createDemandViaRoute($dealer, $vegetable);
        $post = $item->post;
        $post->update(['scheduled_date' => now()->subDay()->toDateString()]); // force overdue

        actingAs($dealer)
            ->put(route('dealer.demands.update', $post), [
                'scheduled_date' => $post->scheduled_date->format('Y-m-d'),
                'items' => [['vegetable_id' => $vegetable->id, 'quantity_kg' => 75]],
            ])
            ->assertSessionHasNoErrors();
    });

});

// ─── Demand Item Lifecycle ────────────────────────────────────────────────────

describe('DemandLifecycle', function () {
    it('dealer can delete a demand', function () {
        $dealer = dealerWithProfile();
        $item = createDemandViaRoute($dealer, createVegetable());
        $post = $item->post;

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull();
    });

    it('deleting a demand soft-deletes the post record', function () {
        $dealer = dealerWithProfile();
        $item = createDemandViaRoute($dealer, createVegetable());
        $post = $item->post;

        actingAs($dealer)
            ->delete(route('dealer.demands.destroy', $post))
            ->assertRedirect();

        expect(Post::find($post->id))->toBeNull()
            ->and(Post::withTrashed()->find($post->id))->not->toBeNull();
    });

});

// ─── Cross-role access ────────────────────────────────────────────────────────

describe('CrossRoleAccess', function () {

    it('farmer cannot access dealer demand routes', function () {
        $farmer = User::factory()->create();
        $farmer->roles()->attach(Role::firstOrCreate(['name' => 'farmer']));

        actingAs($farmer)
            ->post(route('dealer.demands.store'), [])
            ->assertForbidden();
    });

    it('unauthenticated user is redirected from supply and demand routes', function () {
        $this->post(route('farmer.supplies.store'), [])->assertRedirect(route('login'));
        $this->post(route('dealer.demands.store'), [])->assertRedirect(route('login'));
    });

});