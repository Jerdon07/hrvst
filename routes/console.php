<?php

use App\Console\Commands\EvaluateVegetableWatchesCommand;
use App\Console\Commands\ExpireSubscriptionsCommand;
use App\Console\Commands\NotifyPostsDueTodayCommand;
use App\Console\Commands\NotifyPostsReminderCommand;
use App\Console\Commands\SweepExpiredPostItemsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(SweepExpiredPostItemsCommand::class)->daily()->onOneServer();
Schedule::command(ExpireSubscriptionsCommand::class)->daily()->onOneServer();

Schedule::command(EvaluateVegetableWatchesCommand::class)->weekly()->onOneServer();

Schedule::command(NotifyPostsDueTodayCommand::class)->dailyAt('07:00')->onOneServer();
Schedule::command(NotifyPostsReminderCommand::class, ['morning'])->dailyAt('06:00')->onOneServer();
Schedule::command(NotifyPostsReminderCommand::class, ['evening'])->dailyAt('18:00')->onOneServer();

Artisan::command('debug:media', function () {
    $m = \App\Models\Vegetable\Vegetable::first()?->getFirstMedia('vegetable_image');
    $this->line(json_encode([
        'disk' => $m?->disk,
        'url' => $m?->getUrl(),
        'path' => $m?->getPath(),
        's3_url_cfg' => config('filesystems.disks.s3.url'),
        'default_disk' => config('filesystems.default'),
        'media_disk' => config('media-library.disk_name'),
    ], JSON_PRETTY_PRINT));
})->purpose('Debug media disk config');