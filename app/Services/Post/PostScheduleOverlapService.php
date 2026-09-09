<?php

namespace App\Services\Post;

use App\Data\Post\OverlapPosterData;
use App\Data\Post\VegetableOverlapData;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;

class PostScheduleOverlapService
{
    /**
     * @return array<int, VegetableOverlapData> keyed by post_item id
     */
    public function forPost(Post $post): array
    {
        $post->loadMissing('postItems');

        $vegetableIds = $post->postItems->pluck('vegetable_id')->unique()->values();

        if ($vegetableIds->isEmpty()) {
            return [];
        }

        $othersByVegetable = PostItem::query()
            ->ongoing()
            ->whereIn('vegetable_id', $vegetableIds)
            ->whereHas('post', fn ($q) => $q
                ->where('type', $post->type)
                ->where('user_id', '!=', $post->user_id)
                ->whereDate('scheduled_date', $post->scheduled_date)
                ->where('time_slot', $post->time_slot))
            ->with('post.user')
            ->get()
            ->groupBy('vegetable_id');

        return $post->postItems
            ->mapWithKeys(fn (PostItem $item) => [
                $item->id => new VegetableOverlapData(
                    post_item_id: $item->id,
                    vegetable_id: $item->vegetable_id,
                    total_kg: (float) $othersByVegetable->get($item->vegetable_id, collect())->sum('quantity_kg'),
                    posters: OverlapPosterData::collect(
                        $othersByVegetable->get($item->vegetable_id, collect())
                            ->map(fn (PostItem $i) => OverlapPosterData::fromPostItem($i))
                            ->values()
                            ->all()
                    ),
                ),
            ])
            ->all();
    }
}