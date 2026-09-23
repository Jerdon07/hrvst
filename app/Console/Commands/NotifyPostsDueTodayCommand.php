<?php

namespace App\Console\Commands;

use App\Models\Schedule\Post;
use App\Notifications\Push\PostDueTodayNotification;
use Illuminate\Console\Command;

class NotifyPostsDueTodayCommand extends Command
{
    protected $signature = 'posts:notify-due-today';

    protected $description = 'Notify farmers/dealers whose scheduled post is due today. Fires once per post via due_today_notified_at.';

    public function handle(): int
    {
        $count = 0;

        Post::query()
            ->whereDate('scheduled_date', today())
            ->whereNull('due_today_notified_at')
            ->whereHas('postItems', fn ($q) => $q->ongoing())
            ->with('user')
            ->chunkById(200, function ($posts) use (&$count) {
                foreach ($posts as $post) {
                    $post->user->notify(new PostDueTodayNotification($post));
                    $post->update(['due_today_notified_at' => now()]);
                    $count++;
                }
            });

        $this->info("Notified {$count} post(s) due today.");

        return self::SUCCESS;
    }
}
