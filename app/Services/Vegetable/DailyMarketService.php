<?php

namespace App\Services\Vegetable;

use App\Enums\Post\PostTimeSlot;
use App\Enums\PostItemStatus;
use App\Models\Schedule\PostItem;
use App\Models\Vegetable\Vegetable;
use Carbon\CarbonInterface;

class DailyMarketService
{
    /**
     * @return array<int, array{vegetable_id: int, display_name: string, image_url: string, category: ?string, supply_kg: float, demand_kg: float, net_kg: float, supply_posts: int, demand_posts: int}>
     */
    public function forDay(CarbonInterface $date, ?PostTimeSlot $slot = null): array
    {
        $totals = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->whereDate('posts.scheduled_date', $date->toDateString())
            ->when($slot, fn ($q) => $q->where('posts.time_slot', $slot->value))
            ->where('post_items.status', PostItemStatus::Ongoing->value)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->groupBy('post_items.vegetable_id')
            ->selectRaw('post_items.vegetable_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN posts.type = 'supply' THEN post_items.quantity_kg END), 0) as supply_kg")
            ->selectRaw("COALESCE(SUM(CASE WHEN posts.type = 'demand' THEN post_items.quantity_kg END), 0) as demand_kg")
            ->selectRaw("COUNT(DISTINCT CASE WHEN posts.type = 'supply' THEN posts.id END) as supply_posts")
            ->selectRaw("COUNT(DISTINCT CASE WHEN posts.type = 'demand' THEN posts.id END) as demand_posts")
            ->toBase()
            ->get();

        if ($totals->isEmpty()) {
            return [];
        }

        $vegetables = Vegetable::with('category')
            ->whereIn('id', $totals->pluck('vegetable_id'))
            ->get()
            ->keyBy('id');

        return $totals
            ->map(function (object $row) use ($vegetables): array {
                $vegetable = $vegetables[$row->vegetable_id];
                $supply = round((float) $row->supply_kg, 2);
                $demand = round((float) $row->demand_kg, 2);

                return [
                    'vegetable_id' => (int) $row->vegetable_id,
                    'display_name' => $vegetable->display_name,
                    'image_url' => $vegetable->image_url,
                    'category' => $vegetable->category?->name,
                    'supply_kg' => $supply,
                    'demand_kg' => $demand,
                    'net_kg' => round($supply - $demand, 2),
                    'supply_posts' => (int) $row->supply_posts,
                    'demand_posts' => (int) $row->demand_posts,
                ];
            })
            ->sortBy([
                fn (array $a, array $b) => abs($b['net_kg']) <=> abs($a['net_kg']),
                ['display_name', 'asc'],
            ])
            ->values()
            ->all();
    }
}