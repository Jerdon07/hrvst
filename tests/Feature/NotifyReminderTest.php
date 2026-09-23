<?php

use App\Enums\PostItemStatus;
use App\Notifications\Push\PostDueTodayNotification;
use App\Notifications\Push\PostReminderNotification;
use Illuminate\Support\Facades\Notification;

it('notifies a farmer whose supply is due today', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $vegetable = createVegetable();
    createSupplyPost($farmer, $vegetable, [
        'scheduled_date' => today()->toDateString(),
    ]);

    $this->artisan('posts:notify-due-today');

    Notification::assertSentTo($farmer, PostDueTodayNotification::class);
});

it('does not notify a post already marked as notified today', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $post = createSupplyPost($farmer, createVegetable(), [
        'scheduled_date' => today()->toDateString(),
    ]);
    $post->update(['due_today_notified_at' => now()]);

    $this->artisan('posts:notify-due-today');

    Notification::assertNothingSent();
});

it('builds a valid webpush message with post data', function () {
    $farmer = createFarmerUser();
    $vegetable = createVegetable();
    $post = createSupplyPost($farmer, $vegetable, [
        'scheduled_date' => today()->toDateString(),
    ]);

    $notification = new PostDueTodayNotification($post);
    $payload = $notification->toWebPush($farmer, $notification)->toArray();

    expect($payload['body'])->toContain('supply')
        ->and($payload['data']['url'])->toContain('farmer/supplies');
});

it('sends morning and evening reminders independently without double-blocking', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $post = createSupplyPost($farmer, createVegetable(), [
        'scheduled_date' => today()->addDay()->toDateString(),
    ]);

    $this->artisan('posts:notify-reminder', ['slot' => 'morning']);
    $this->artisan('posts:notify-reminder', ['slot' => 'evening']);

    Notification::assertSentToTimes($farmer, PostReminderNotification::class, 2);

    expect($post->fresh())
        ->reminder_morning_notified_at->not->toBeNull()
        ->reminder_evening_notified_at->not->toBeNull();
});

it('does not re-notify the same slot twice', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    createSupplyPost($farmer, createVegetable(), [
        'scheduled_date' => today()->addDay()->toDateString(),
    ]);

    $this->artisan('posts:notify-reminder', ['slot' => 'morning']);
    $this->artisan('posts:notify-reminder', ['slot' => 'morning']);

    Notification::assertSentToTimes($farmer, PostReminderNotification::class, 1);
});

it('builds a valid webpush body for the reminder', function () {
    $farmer = createFarmerUser();
    $post = createSupplyPost($farmer, createVegetable(), [
        'scheduled_date' => today()->addDay()->toDateString(),
    ]);

    $notification = new PostReminderNotification($post);
    $payload = $notification->toWebPush($farmer, $notification)->toArray();

    expect($payload['title'])->toBe('Schedule due tomorrow')
        ->and($payload['body'])->toContain('tomorrow')
        ->and($payload['data']['url'])->toContain('farmer/supplies');
});

it('does not notify a post scheduled for a different day', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    createSupplyPost($farmer, createVegetable(), [
        'scheduled_date' => today()->addDay()->toDateString(),
    ]);

    $this->artisan('posts:notify-due-today');

    Notification::assertNothingSent();
});

it('does not notify a post whose only item is already fulfilled', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $post = createSupplyPost($farmer, createVegetable(), [
        'scheduled_date' => today()->toDateString(),
    ]);
    $post->postItems()->update(['status' => PostItemStatus::Fulfilled]);

    $this->artisan('posts:notify-due-today');

    Notification::assertNothingSent();
});

it('marks the post as notified after sending', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $post = createSupplyPost($farmer, createVegetable(), [
        'scheduled_date' => today()->toDateString(),
    ]);

    $this->artisan('posts:notify-due-today');

    expect($post->fresh()->due_today_notified_at)->not->toBeNull();
});

it('notifies both a farmer supply and a dealer demand due the same day', function () {
    Notification::fake();

    $farmer = createFarmerUser();
    $dealer = createDealerUser();
    $vegetable = createVegetable();

    createSupplyPost($farmer, $vegetable, ['scheduled_date' => today()->toDateString()]);
    createDemandPost($dealer, $vegetable, ['scheduled_date' => today()->toDateString()]);

    $this->artisan('posts:notify-due-today');

    Notification::assertSentTo($farmer, PostDueTodayNotification::class);
    Notification::assertSentTo($dealer, PostDueTodayNotification::class);
});
