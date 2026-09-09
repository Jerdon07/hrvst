<?php

namespace App\Services\Vegetable;

use App\Models\Vegetable\Vegetable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VegetableActivityService
{
    /**
     * Hard cap on how far back the paginated market volume display is
     * allowed to reach — the "5 years max" product rule.
     */
    public const int TOTAL_HISTORY_MONTHS = 60;

    /**
     * Widest offset the display window is allowed to page back to. Derived
     * from TOTAL_HISTORY_MONTHS minus the initial 6-month default window, so
     * the last historical page still lands exactly on the 5-year boundary
     * (see VegetableDetailService::resolveActivityWindowMonths()). Both the
     * controller (validation) and the frontend (disabling "previous") key
     * off this.
     */
    public const int MAX_OFFSET_MONTHS = self::TOTAL_HISTORY_MONTHS - 6;

    /**
     * Build monthly activity data for a given number of past months.
     *
     * Pass months: 36 from VarietyService to get 3-year history in a single
     * query for forecasting; the chart uses array_slice(-12) of that result.
     *
     * $endOffsetMonths shifts the *entire* window back in time without
     * changing its size — month index 0 becomes "$endOffsetMonths months
     * ago" instead of "now". This exists solely for the paginated market
     * volume display; forecast/analytics callers must always leave this at
     * 0 so the seasonal trend math stays anchored to the present, regardless
     * of what window the user happens to be browsing on screen.
     *
     * `has_data` flags months that have an actual DB row vs zero-padded gaps.
     * The chart renders both identically (zero bars for gaps), but
     * computeForecast() must skip padded months to avoid phantom-zero dilution
     * of seasonal baselines and trend ratios — the root cause of the
     * demand > supply inversion seen in the forecast.
     *
     * @return array<int, array{
     *     month: string,
     *     label: string,
     *     has_data: bool,
     *     supply_fulfilled_kg: float,
     *     supply_expired_kg: float,
     *     demand_fulfilled_kg: float,
     *     demand_expired_kg: float,
     * }>
     */
    public function buildMonthlyActivity(int $vegetableId, int $months = 12, int $endOffsetMonths = 0): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1 + $endOffsetMonths)->toDateString();
        $end = now()->startOfMonth()->subMonths($endOffsetMonths)->endOfMonth()->toDateString();

        $rows = DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $vegetableId)
            ->whereBetween('period_date', [$start, $end])
            ->select(['period_date', 'supply_fulfilled_kg', 'supply_expired_kg', 'demand_fulfilled_kg', 'demand_expired_kg'])
            ->get()
            ->groupBy(fn ($row) => Carbon::parse($row->period_date)->format('Y-m'))
            ->map(fn ($group) => (object) [
                'supply_fulfilled_kg' => $group->sum('supply_fulfilled_kg'),
                'supply_expired_kg' => $group->sum('supply_expired_kg'),
                'demand_fulfilled_kg' => $group->sum('demand_fulfilled_kg'),
                'demand_expired_kg' => $group->sum('demand_expired_kg'),
            ]);

        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i + $endOffsetMonths);
            $key = $date->format('Y-m');
            $row = $rows->get($key);

            $result[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'has_data' => $row !== null,
                'supply_fulfilled_kg' => (float) ($row?->supply_fulfilled_kg ?? 0),
                'supply_expired_kg' => (float) ($row?->supply_expired_kg ?? 0),
                'demand_fulfilled_kg' => (float) ($row?->demand_fulfilled_kg ?? 0),
                'demand_expired_kg' => (float) ($row?->demand_expired_kg ?? 0),
            ];
        }

        return $result;
    }

    /**
     * Same has_data/zero-padding contract as buildMonthlyActivity(), but for every
     * vegetable at once. This is the single source of truth both analytics
     * services must use — VegetableAnalyticsService (per-vegetable forecast) and
     * VegetableWasteAnalyticsService (platform-wide waste ranking) were
     * previously reading the same table through two different queries with
     * different gap-handling, which is how they could silently drift apart.
     *
     * @return Collection<int, array<int, array{
     *     month: string, label: string, has_data: bool,
     *     supply_fulfilled_kg: float, supply_expired_kg: float,
     *     demand_fulfilled_kg: float, demand_expired_kg: float,
     * }>> keyed by vegetable_id
     */
    public function buildMonthlyActivityForAllVegetables(int $months = self::TOTAL_HISTORY_MONTHS): Collection
    {
        $vegetableIds = Vegetable::pluck('id');
        $start = now()->startOfMonth()->subMonths($months - 1)->toDateString();
        $end = now()->startOfMonth()->endOfMonth()->toDateString();

        $rowsByVegetable = DB::table('vegetable_monthly_stats')
            ->whereBetween('period_date', [$start, $end])
            ->select(['vegetable_id', 'period_date', 'supply_fulfilled_kg', 'supply_expired_kg', 'demand_fulfilled_kg', 'demand_expired_kg'])
            ->get()
            ->groupBy('vegetable_id')
            ->map(fn ($group) => $group->groupBy(fn ($row) => Carbon::parse($row->period_date)->format('Y-m'))
                ->map(fn ($monthRows) => (object) [
                    'supply_fulfilled_kg' => $monthRows->sum('supply_fulfilled_kg'),
                    'supply_expired_kg' => $monthRows->sum('supply_expired_kg'),
                    'demand_fulfilled_kg' => $monthRows->sum('demand_fulfilled_kg'),
                    'demand_expired_kg' => $monthRows->sum('demand_expired_kg'),
                ]));

        return $vegetableIds->mapWithKeys(
            fn ($vegetableId) => [$vegetableId => $this->zeroPadMonths($rowsByVegetable->get($vegetableId, collect()), $months)]
        );
    }

    private function zeroPadMonths(Collection $rows, int $months): array
    {
        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonths($i);
            $key = $date->format('Y-m');
            $row = $rows->get($key);

            $result[] = [
                'month' => $key,
                'label' => $date->format('M Y'),
                'has_data' => $row !== null,
                'supply_fulfilled_kg' => (float) ($row->supply_fulfilled_kg ?? 0),
                'supply_expired_kg' => (float) ($row->supply_expired_kg ?? 0),
                'demand_fulfilled_kg' => (float) ($row->demand_fulfilled_kg ?? 0),
                'demand_expired_kg' => (float) ($row->demand_expired_kg ?? 0),
            ];
        }

        return $result;
    }
}
