<?php

namespace App\Services\Vegetable;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformActivityService
{
    private const int HISTORY_YEARS = 5;

    private const int CACHE_TTL_SECONDS = 43200;

    /**
     * Distinct active farmers and dealers per calendar month, keyed by 'Y-m'.
     * "Active" = created at least one post of that type in that month.
     *
     * This is the denominator every trend calculation must divide by before
     * comparing periods — without it, platform user growth masquerades as
     * per-vegetable market demand growth.
     */
    public function monthlyActiveCounts(): Collection
    {
        return Cache::remember(
            'platform_monthly_active_counts',
            [3600, self::CACHE_TTL_SECONDS],
            fn () => $this->resolve(),
        );
    }

    private function resolve(): Collection
    {
        $start = now()->startOfMonth()->subYears(self::HISTORY_YEARS)->toDateString();

        return DB::table('posts')
            ->where('created_at', '>=', $start)
            ->whereNull('deleted_at')
            ->selectRaw("TO_CHAR(created_at, 'YYYY-MM') as period")
            ->selectRaw("COUNT(DISTINCT CASE WHEN type = 'supply' THEN user_id END) as active_farmers")
            ->selectRaw("COUNT(DISTINCT CASE WHEN type = 'demand' THEN user_id END) as active_dealers")
            ->groupByRaw("TO_CHAR(created_at, 'YYYY-MM')")
            ->get()
            ->keyBy('period');
    }
}
