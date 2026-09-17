<?php

namespace App\Services\Shared;

use App\Data\Profile\MonthlyVolumeData;
use App\Data\Profile\TopVegetableData;
use App\Data\Profile\UserInsightsData;
use App\Enums\PostType;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\Vegetable\Vegetable;
use Carbon\Carbon;

class PostItemInsightsService
{
    private const int TOP_VARIETIES_LIMIT = 5;

    private const int MONTHLY_VOLUME_MONTHS = 6;

    public function compute(int $userId, PostType $type): UserInsightsData
    {
        $cadence = $this->cadence($userId, $type);

        return new UserInsightsData(
            fulfillment_rate: $this->fulfillmentRate($userId, $type),
            total_posts: $cadence['total_posts'],
            posts_per_month: $cadence['posts_per_month'],
            last_active: $cadence['last_active'],
            last_active_human: $cadence['last_active_human'],
            top_varieties: $this->topVarieties($userId, $type),
            monthly_volume: $this->monthlyVolume($userId, $type),
        );
    }

    private function fulfillmentRate(int $userId, PostType $type): ?float
    {
        $row = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->where('posts.user_id', $userId)
            ->where('posts.type', $type->value)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->selectRaw("
                COUNT(CASE WHEN post_items.status = 'fulfilled' THEN 1 END) as fulfilled,
                COUNT(CASE WHEN post_items.status = 'expired' THEN 1 END) as expired
            ")
            ->first();

        $fulfilled = (int) ($row->fulfilled ?? 0);
        $expired = (int) ($row->expired ?? 0);
        $total = $fulfilled + $expired;

        return $total > 0 ? round($fulfilled / $total, 4) : null;
    }

    /**
     * @return array{total_posts: int, posts_per_month: float, last_active: ?string, last_active_human: ?string}
     */
    private function cadence(int $userId, PostType $type): array
    {
        $row = Post::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->selectRaw('COUNT(*) as total_posts, MIN(created_at) as first_post_at, MAX(created_at) as last_post_at')
            ->first();

        $totalPosts = (int) ($row->total_posts ?? 0);

        if ($totalPosts === 0 || $row->first_post_at === null) {
            return [
                'total_posts' => 0,
                'posts_per_month' => 0.0,
                'last_active' => null,
                'last_active_human' => null,
            ];
        }

        $firstPost = Carbon::parse($row->first_post_at);
        $lastPost = Carbon::parse($row->last_post_at);

        $activeMonths = $firstPost->diffInMonths(now()) + 1;

        return [
            'total_posts' => $totalPosts,
            'posts_per_month' => round($totalPosts / $activeMonths, 1),
            'last_active' => $lastPost->format('M j, Y'),
            'last_active_human' => $lastPost->diffForHumans(),
        ];
    }

    /**
     * @return TopVegetableData[]
     */
    private function topVarieties(int $userId, PostType $type): array
    {
        $rows = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->where('posts.user_id', $userId)
            ->where('posts.type', $type->value)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->groupBy('post_items.vegetable_id')
            ->selectRaw('post_items.vegetable_id, COUNT(*) as post_count, SUM(post_items.quantity_kg) as total_kg')
            ->orderByDesc('total_kg')
            ->limit(self::TOP_VARIETIES_LIMIT)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $vegetables = Vegetable::whereIn('id', $rows->pluck('vegetable_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($vegetables) {
            /** @var Vegetable|null $vegetable */
            $vegetable = $vegetables->get($row->vegetable_id);

            return new TopVegetableData(
                id: (int) $row->vegetable_id,
                display_name: $vegetable?->display_name ?? 'Unknown',
                image_url: $vegetable?->getFirstMediaUrl('vegetable_image') ?? '',
                post_count: (int) $row->post_count,
                value_kg: round((float) $row->total_kg, 2),
            );
        })->values()->all();
    }

    /**
     * @return MonthlyVolumeData[]
     */
    private function monthlyVolume(int $userId, PostType $type): array
    {
        $months = self::MONTHLY_VOLUME_MONTHS;
        $start = now()->startOfMonth()->subMonths($months - 1)->toDateString();

        $rows = PostItem::query()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->where('posts.user_id', $userId)
            ->where('posts.type', $type->value)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->where('posts.created_at', '>=', $start)
            ->select('posts.created_at', 'post_items.quantity_kg')
            ->get()
            ->groupBy(fn ($row) => \Carbon\Carbon::parse($row->created_at)->format('Y-m'))
            ->map(fn ($group) => $group->sum('quantity_kg'));

        $result = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->format('Y-m');

            $result[] = new MonthlyVolumeData(
                month: $key,
                label: $date->format('M Y'),
                value_kg: round((float) ($rows->get($key) ?? 0), 2),
            );
        }

        return $result;
    }
}
