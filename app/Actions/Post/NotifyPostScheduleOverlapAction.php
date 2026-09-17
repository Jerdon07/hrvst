<?php

namespace App\Actions\Post;

use App\Enums\PostItemStatus;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\User;
use App\Notifications\PostScheduleOverlapNotification;
use Illuminate\Support\Facades\Notification;

final class NotifyPostScheduleOverlapAction
{
    public function handle(Post $post): void
    {
        $post->loadMissing('postItems.vegetable');

        foreach ($post->postItems as $item) {
            $this->notifyForItem($post, $item);
        }
    }

    private function notifyForItem(Post $post, PostItem $item): void
    {
        if ($post->scheduled_date === null || $post->time_slot === null) {
            return;
        }

        $recipientIds = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->where('posts.scheduled_date', $post->scheduled_date->toDateString())
            ->where('posts.time_slot', $post->time_slot->value)
            ->where('post_items.vegetable_id', $item->vegetable_id)
            ->where('post_items.status', PostItemStatus::Ongoing->value)
            ->where('posts.user_id', '!=', $post->user_id)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->distinct()
            ->pluck('posts.user_id');

        if ($recipientIds->isEmpty()) {
            return;
        }

        Notification::send(
            User::whereIn('id', $recipientIds)->get(),
            new PostScheduleOverlapNotification(
                $item->vegetable,
                $post->type,
                $post->scheduled_date->toDateString(),
                $post->time_slot,
                (float) $item->quantity_kg,
            ),
        );
    }
}