<?php

use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Enums\Post\PostTimeSlot;
use App\Enums\Post\PostType;
use App\Models\Billing\Subscription;
use App\Notifications\Push\PostScheduleOverlapNotification;
use App\Notifications\VegetableOutlookAlert;

use function Pest\Laravel\actingAs;

function grantNotificationAccess($user, SubscriptionFeature $feature): void
{
    Subscription::create([
        'user_id' => $user->id,
        'feature' => $feature,
        'plan' => SubscriptionPlan::Monthly,
        'status' => SubscriptionStatus::Active,
        'amount_cents' => 9_900,
        'currency' => 'PHP',
        'payment_gateway' => 'mock',
        'payment_reference' => 'mock_'.uniqid(),
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

it('shows the exact outlook label to a subscribed farmer', function () {
    $farmer = createFarmerUser();
    grantNotificationAccess($farmer, SubscriptionFeature::FarmerForecasts);
    $vegetable = createVegetable();

    $farmer->notify(new VegetableOutlookAlert($vegetable, [
        'band' => ImbalanceBand::Oversupply,
        'starts_in_months' => 1,
        'duration_months' => 2,
        'label' => 'Expected to be oversupplied next month for about 2 months.',
    ]));

    $notification = actingAs($farmer)->getJson(route('notifications.index'))->assertOk()->json('notifications')[0];

    expect($notification['detail_locked'])->toBeFalse()
        ->and($notification['message'])->toBe('Expected to be oversupplied next month for about 2 months.');
});

it('shows only the generic band copy to an unsubscribed farmer, never the exact timing', function () {
    $farmer = createFarmerUser();
    $vegetable = createVegetable();

    $farmer->notify(new VegetableOutlookAlert($vegetable, [
        'band' => ImbalanceBand::Oversupply,
        'starts_in_months' => 1,
        'duration_months' => 2,
        'label' => 'Expected to be oversupplied next month for about 2 months.',
    ]));

    $notification = actingAs($farmer)->getJson(route('notifications.index'))->assertOk()->json('notifications')[0];

    expect($notification['detail_locked'])->toBeTrue()
        ->and($notification['message'])->toBe('Oversupply expected — subscribe for exact timing.')
        ->and($notification['message'])->not->toContain('next month');
});

it('never locks a schedule overlap notification, regardless of subscription', function () {
    $dealer = createDealerUser();
    $vegetable = createVegetable();

    $dealer->notify(new PostScheduleOverlapNotification(
        $vegetable, PostType::Supply, now()->addDay()->toDateString(), PostTimeSlot::Morning, 100.0,
    ));

    $notification = actingAs($dealer)->getJson(route('notifications.index'))->assertOk()->json('notifications')[0];

    expect($notification['detail_locked'])->toBeFalse()
        ->and($notification['kind'])->toBe('schedule_overlap')
        ->and($notification['url'])->not->toBeEmpty();
});

it('counts unread notifications across every notification type, not just one', function () {
    $farmer = createFarmerUser();
    $vegetable = createVegetable();

    $farmer->notify(new VegetableOutlookAlert($vegetable, ['band' => ImbalanceBand::Oversupply, 'label' => 'x']));
    $farmer->notify(new PostScheduleOverlapNotification(
        $vegetable, PostType::Supply, now()->addDay()->toDateString(), PostTimeSlot::Morning, 100.0,
    ));

    $response = actingAs($farmer)->getJson(route('notifications.index'))->assertOk();

    expect($response->json('unread_count'))->toBe(2);
});

it('marks a notification as read', function () {
    $farmer = createFarmerUser();
    $farmer->notify(new VegetableOutlookAlert(createVegetable(), ['band' => ImbalanceBand::Oversupply, 'label' => 'x']));

    $id = $farmer->notifications()->first()->id;

    actingAs($farmer)->post(route('notifications.read', $id))->assertOk();

    expect($farmer->unreadNotifications()->count())->toBe(0);
});
