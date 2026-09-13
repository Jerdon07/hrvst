<?php

namespace App\Services\Post;

use App\Data\Post\OverlapPosterData;
use App\Data\Post\VegetableOverlapData;
use App\Enums\PostItemStatus;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

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
            ->where('status', PostItemStatus::Ongoing->value)
            ->whereIn('vegetable_id', $vegetableIds)
            ->whereHas('post', fn ($q) => $q
                ->where('user_id', '!=', $post->user_id)
                ->whereDate('scheduled_date', $post->scheduled_date->toDateString())
                ->where('time_slot', $post->time_slot->value))
            ->with('post.user')
            ->get()
            ->groupBy('vegetable_id');

        return $post->postItems
            ->mapWithKeys(function (PostItem $item) use ($othersByVegetable): array {
                $others = $othersByVegetable->get($item->vegetable_id, collect());
                $posters = $this->posterGroups($others);

                return [$item->id => new VegetableOverlapData(
                    post_item_id: $item->id,
                    vegetable_id: $item->vegetable_id,
                    total_supplies_kg: $posters['total_supplies_kg'],
                    total_demands_kg: $posters['total_demands_kg'],
                    posters: $posters['posters'],
                    supply_posters: $posters['supply_posters'],
                    demand_posters: $posters['demand_posters'],
                )];
            })
            ->all();
    }

    /**
     * @param  Collection<int, int|string>  $vegetableIds
     * @return array<int, VegetableOverlapData> keyed by vegetable id
     */
    public function forPostAt(
        Post $post,
        CarbonInterface $scheduledDate,
        PostTimeSlot $timeSlot,
        Collection $vegetableIds,
    ): array {
        $post->loadMissing('postItems');

        $vegetableIds = $vegetableIds
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($vegetableIds->isEmpty()) {
            return [];
        }

        $othersByVegetable = PostItem::query()
            ->where('status', PostItemStatus::Ongoing->value)
            ->whereIn('vegetable_id', $vegetableIds)
            ->whereHas('post', fn ($q) => $q
                ->where('user_id', '!=', $post->user_id)
                ->whereDate('scheduled_date', $scheduledDate->toDateString())
                ->where('time_slot', $timeSlot->value))
            ->with('post.user')
            ->get()
            ->groupBy('vegetable_id');

        return $vegetableIds
            ->mapWithKeys(function (int $vegetableId) use ($post, $othersByVegetable): array {
                $others = $othersByVegetable->get($vegetableId, collect());
                $posters = $this->posterGroups($others);

                return [$vegetableId => new VegetableOverlapData(
                    post_item_id: (int) ($post->postItems->firstWhere('vegetable_id', $vegetableId)?->id ?? 0),
                    vegetable_id: $vegetableId,
                    total_supplies_kg: $posters['total_supplies_kg'],
                    total_demands_kg: $posters['total_demands_kg'],
                    posters: $posters['posters'],
                    supply_posters: $posters['supply_posters'],
                    demand_posters: $posters['demand_posters'],
                )];
            })
            ->all();
    }

    /**
     * @param  Collection<int, PostItem>  $items
     * @return array{
     *     posters: array<int, OverlapPosterData>,
     *     supply_posters: array<int, OverlapPosterData>,
     *     demand_posters: array<int, OverlapPosterData>,
     *     total_supplies_kg: float,
     *     total_demands_kg: float,
     * }
     */
    private function posterGroups(Collection $items): array
    {
        $posters = $items
            ->map(fn (PostItem $item) => OverlapPosterData::fromPostItem($item))
            ->values()
            ->all();

        $supplyItems = $items->filter(fn (PostItem $item): bool => $item->post->type === PostType::Supply);
        $demandItems = $items->filter(fn (PostItem $item): bool => $item->post->type === PostType::Demand);

        return [
            'posters' => $posters,
            'supply_posters' => $supplyItems
                ->map(fn (PostItem $item) => OverlapPosterData::fromPostItem($item))
                ->values()
                ->all(),
            'demand_posters' => $demandItems
                ->map(fn (PostItem $item) => OverlapPosterData::fromPostItem($item))
                ->values()
                ->all(),
            'total_supplies_kg' => (float) $supplyItems->sum('quantity_kg'),
            'total_demands_kg' => (float) $demandItems->sum('quantity_kg'),
        ];
    }
}