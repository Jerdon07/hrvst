<?php

namespace App\Console\Commands;

use App\Models\Schedule\Post;
use App\Notifications\Push\PostReminderNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('posts:notify-reminder {slot : morning or evening}')]
#[Description('Notify farmers/dealers of posts scheduled for tomorrow')]
class NotifyPostsReminderCommand extends Command
{
    private const array SLOT_COLUMNS = [
        'morning' => 'reminder_morning_notified_at',
        'evening' => 'reminder_evening_notified_at',
    ];

    public function handle()
    {
        $slot = $this->argument('slot');

        if (! array_key_exists($slot, self::SLOT_COLUMNS)) {
            $this->error('Slot must be "morning" or "evening".');

            return self::FAILURE;
        }

        $column = self::SLOT_COLUMNS[$slot];
        $count = 0;

        Post::query()
            ->whereDate('scheduled_date', today()->addDay())
            ->whereNull($column)
            ->whereHas('postItems', fn ($q) => $q->ongoing())
            ->with('user')
            ->chunkById(200, function ($posts) use (&$count, $column) {
                foreach ($posts as $post) {
                    $post->user->notify(new PostReminderNotification($post));
                    $post->update([$column => now()]);
                    $count++;
                }
            });

        $this->info("Sent {$count} {$slot} reminder(s) for tomorrow's posts.");

        return self::SUCCESS;
    }
}
