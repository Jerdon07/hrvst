<?php

namespace App\Actions\Post;

use App\Enums\Post\PostType;
use App\Enums\PostItemStatus;
use App\Models\Schedule\Post;
use Illuminate\Support\Facades\DB;

final class CreatePostAction
{
    public function __construct(private NotifyPostScheduleOverlapAction $notifyOverlap) {}

    public function handle(int $userId, PostType $type, array $validated): Post
    {
        $post = DB::transaction(function () use ($userId, $type, $validated) {
            $post = Post::create([
                'user_id' => $userId,
                'type' => $type,
                'scheduled_date' => $validated['scheduled_date'],
                'time_slot' => $validated['time_slot'],
            ]);

            foreach ($validated['items'] as $item) {
                $post->postItems()->create([
                    'vegetable_id' => $item['vegetable_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'status' => PostItemStatus::Ongoing,
                ]);
            }

            return $post->load('postItems.vegetable');
        });

        $this->notifyOverlap->handle($post);

        return $post;
    }
}
