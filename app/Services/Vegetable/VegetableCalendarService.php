<?php

namespace App\Services\Vegetable;

use App\Data\Vegetable\VegetableCalendarItemData;
use App\Enums\PostItemStatus;
use App\Models\Schedule\PostItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VegetableCalendarService
{
    public function buildForMonth(int $vegetableId, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $aggregates = $this->fetchAggregates($vegetableId, $start, $end);
        $items = $this->fetchItems($vegetableId, $start, $end);

        return $this->mergeIntoSchedule($aggregates, $items);
    }

    private function fetchAggregates(int $vegetableId, string $start, string $end): Collection
    {
        return PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->selectRaw('DATE(posts.scheduled_date) as date')
            ->selectRaw("COALESCE(posts.time_slot, 'morning') as slot")
            ->selectRaw('posts.type')
            ->selectRaw('SUM(post_items.quantity_kg) as total_kg')
            ->selectRaw('COUNT(DISTINCT posts.id) as posts_count')
            ->where('post_items.vegetable_id', $vegetableId)
            ->whereBetween('posts.scheduled_date', [$start, $end])
            ->whereNull('posts.deleted_at')
            ->whereIn('post_items.status', [
                PostItemStatus::Ongoing->value,
                PostItemStatus::Fulfilled->value,
                PostItemStatus::Expired->value,
            ])
            ->groupByRaw("DATE(posts.scheduled_date), COALESCE(posts.time_slot, 'morning'), posts.type")
            ->orderByRaw('date, slot, posts.type')
            ->get();
    }

    private function fetchItems(int $vegetableId, string $start, string $end): Collection
    {
        return PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->join('vegetables', 'vegetables.id', '=', 'post_items.vegetable_id')
            ->join('users', 'users.id', '=', 'posts.user_id')
            ->select([
                'post_items.id',
                'post_items.post_id',
                'post_items.vegetable_id',
                'post_items.quantity_kg',
                'post_items.status',
                'posts.type',
            ])
            ->selectRaw("COALESCE(posts.time_slot, 'morning') as slot")
            ->selectRaw('DATE(posts.scheduled_date) as date')
            ->selectRaw('vegetables.variety_name as variety_name')
            ->selectRaw('users.name as poster_name')
            ->selectRaw('users.phone_number as poster_phone')
            ->where('post_items.vegetable_id', $vegetableId)
            ->whereBetween('posts.scheduled_date', [$start, $end])
            ->whereNull('posts.deleted_at')
            ->whereIn('post_items.status', [
                PostItemStatus::Ongoing->value,
                PostItemStatus::Fulfilled->value,
                PostItemStatus::Expired->value,
            ])
            ->orderByRaw('date, slot, posts.type')
            ->get();
    }

    private function mergeIntoSchedule(Collection $aggregates, Collection $items): array
    {
        $schedule = [];

        foreach ($aggregates as $row) {
            [$date, $slot, $type] = [$row->date, $row->slot, $row->type];
            $schedule[$date][$slot] ??= $this->emptySlot();
            $schedule[$date][$slot]["{$type}_kg"] = (float) $row->total_kg;
            $schedule[$date][$slot]["{$type}_posts_count"] = (int) $row->posts_count;
        }

        foreach ($schedule as &$slots) {
            foreach ($slots as &$slot) {
                $slot['net_kg'] = round($slot['supply_kg'] - $slot['demand_kg'], 2);
            }
        }
        unset($slots, $slot);

        foreach ($items as $item) {
            [$date, $slot] = [$item->date, $item->slot];
            if (! isset($schedule[$date][$slot])) {
                continue;
            }

            $schedule[$date][$slot]['items'][] = VegetableCalendarItemData::fromQueryRow($item);
        }

        return $schedule;
    }

    private function emptySlot(): array
    {
        return [
            'supply_kg' => 0.0, 'demand_kg' => 0.0, 'net_kg' => 0.0,
            'supply_posts_count' => 0, 'demand_posts_count' => 0, 'items' => [],
        ];
    }
}