<?php

use App\Enums\PostItemStatus;
use App\Enums\PostTimeSlot;
use App\Models\Schedule\PostItem;
use App\Notifications\PostScheduleOverlapNotification;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function supplyPayload(int $vegetableId, string $date, string $timeSlot = 'morning'): array
{
    return [
        'scheduled_date' => $date,
        'time_slot' => $timeSlot,
        'items' => [['vegetable_id' => $vegetableId, 'quantity_kg' => 100]],
    ];
}

function demandPayload(int $vegetableId, string $date, string $timeSlot = 'morning'): array
{
    return [
        'scheduled_date' => $date,
        'time_slot' => $timeSlot,
        'items' => [['vegetable_id' => $vegetableId, 'quantity_kg' => 50]],
    ];
}

// ─── Create ───────────────────────────────────────────────────────────────────

describe('schedule overlap notification on create', function () {
    it('notifies a dealer whose demand matches the new supply exactly', function () {
        Notification::fake();

        $date = now()->addDays(3)->toDateString();
        $vegetable = createVegetable();

        $dealer = createDealerUser();
        createDemandPost($dealer, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);

        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date))
            ->assertRedirect();

        Notification::assertSentTo(
            $dealer,
            PostScheduleOverlapNotification::class,
            fn ($notification, $channels) => in_array('database', $channels, true)
        );
    });

    it('does not notify the actor about their own other matching posts', function () {
        Notification::fake();

        $date = now()->addDays(3)->toDateString();
        $vegetable = createVegetable();
        $farmer = createFarmerUser();

        createSupplyPost($farmer, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date))
            ->assertRedirect();

        Notification::assertNotSentTo($farmer, PostScheduleOverlapNotification::class);
    });

    it('does not notify when vegetable, date, or slot do not all match', function () {
        Notification::fake();

        $vegetable = createVegetable();
        $otherVegetable = createVegetable();
        $date = now()->addDays(3)->toDateString();

        $wrongVegetable = createDealerUser();
        createDemandPost($wrongVegetable, $otherVegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);

        $wrongDate = createDealerUser();
        createDemandPost($wrongDate, $vegetable, [
            'scheduled_date' => now()->addDays(10)->toDateString(),
            'time_slot' => PostTimeSlot::Morning,
        ]);

        $wrongSlot = createDealerUser();
        createDemandPost($wrongSlot, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Evening,
        ]);

        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date))
            ->assertRedirect();

        Notification::assertNotSentTo($wrongVegetable, PostScheduleOverlapNotification::class);
        Notification::assertNotSentTo($wrongDate, PostScheduleOverlapNotification::class);
        Notification::assertNotSentTo($wrongSlot, PostScheduleOverlapNotification::class);
    });

    it('does not notify a matching post whose item is no longer ongoing', function () {
        Notification::fake();

        $date = now()->addDays(3)->toDateString();
        $vegetable = createVegetable();

        $dealer = createDealerUser();
        $demand = createDemandPost($dealer, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);
        $demand->postItems()->update(['status' => PostItemStatus::Fulfilled]);

        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date))
            ->assertRedirect();

        Notification::assertNotSentTo($dealer, PostScheduleOverlapNotification::class);
    });

    it('notifies every distinct matching poster, not just the first', function () {
        Notification::fake();

        $date = now()->addDays(3)->toDateString();
        $vegetable = createVegetable();

        $dealerA = createDealerUser();
        $dealerB = createDealerUser();

        createDemandPost($dealerA, $vegetable, ['scheduled_date' => $date, 'time_slot' => PostTimeSlot::Morning]);
        createDemandPost($dealerB, $vegetable, ['scheduled_date' => $date, 'time_slot' => PostTimeSlot::Morning]);

        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date))
            ->assertRedirect();

        Notification::assertSentTo($dealerA, PostScheduleOverlapNotification::class);
        Notification::assertSentTo($dealerB, PostScheduleOverlapNotification::class);
    });

    it('builds a database payload containing a deep link to the vegetable page', function () {
        Notification::fake();

        $date = now()->addDays(3)->toDateString();
        $vegetable = createVegetable();

        $dealer = createDealerUser();
        createDemandPost($dealer, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);

        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date))
            ->assertRedirect();

        Notification::assertSentTo(
            $dealer,
            PostScheduleOverlapNotification::class,
            function ($notification) use ($vegetable, $dealer) {
                $payload = $notification->toArray($dealer);

                return $payload['vegetable_id'] === $vegetable->id
                    && str_contains($payload['url'], route('vegetables.show', $vegetable));
            }
        );
    });

    it('carries the actor\'s posted quantity_kg, not the recipient\'s own', function () {
        Notification::fake();

        $date = now()->addDays(3)->toDateString();
        $vegetable = createVegetable();

        $dealer = createDealerUser();
        // Recipient's own demand quantity (50kg) must NOT leak into the payload —
        // this asserts we're reading $item->quantity_kg off the actor's post,
        // not accidentally off the matched recipient's post.
        createDemandPost($dealer, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Morning,
        ]);

        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date))
            ->assertRedirect();

        Notification::assertSentTo(
            $dealer,
            PostScheduleOverlapNotification::class,
            function ($notification) use ($dealer) {
                $payload = $notification->toArray($dealer);

                return $payload['quantity_kg'] === 100.0
                    && str_contains($payload['message'], '100')
                    && ! str_contains($payload['message'], '100.00');
            }
        );
    });

    it('renders scheduled_date as human-readable prose in the message, not raw ISO', function () {
        Notification::fake();

        $date = now()->addDays(3)->startOfDay();
        $vegetable = createVegetable();

        $dealer = createDealerUser();
        createDemandPost($dealer, $vegetable, [
            'scheduled_date' => $date->toDateString(),
            'time_slot' => PostTimeSlot::Morning,
        ]);

        $farmer = createFarmerUser();

        actingAs($farmer)
            ->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, $date->toDateString()))
            ->assertRedirect();

        Notification::assertSentTo(
            $dealer,
            PostScheduleOverlapNotification::class,
            function ($notification) use ($dealer, $date) {
                $payload = $notification->toArray($dealer);

                // Database payload keeps the raw ISO date for machine use...
                $isoIntact = $payload['scheduled_date'] === $date->toDateString();

                // ...but the human-facing message must not leak that ISO
                // string, and must contain the formatted month name instead.
                $messageIsHuman = ! str_contains($payload['message'], $date->toDateString())
                    && str_contains($payload['message'], $date->format('M j'));

                return $isoIntact && $messageIsHuman;
            }
        );
    });
});

// ─── Update ───────────────────────────────────────────────────────────────────

describe('schedule overlap notification on update', function () {
    it('notifies a matching poster when an existing post is edited into overlap', function () {
        Notification::fake();

        $date = now()->addDays(6)->toDateString();
        $vegetable = createVegetable();

        $dealer = createDealerUser();
        createDemandPost($dealer, $vegetable, [
            'scheduled_date' => $date,
            'time_slot' => PostTimeSlot::Afternoon,
        ]);

        $farmer = createFarmerUser();

        actingAs($farmer)->post(route('farmer.supplies.store'), supplyPayload($vegetable->id, now()->addDays(2)->toDateString()));

        $post = PostItem::latest('id')->firstOrFail()->post;

        Notification::assertNotSentTo($dealer, PostScheduleOverlapNotification::class);

        actingAs($farmer)
            ->put(route('farmer.supplies.update', $post), [
                'scheduled_date' => $date,
                'time_slot' => 'afternoon',
            ])
            ->assertRedirect();

        Notification::assertSentTo($dealer, PostScheduleOverlapNotification::class);
    });
});