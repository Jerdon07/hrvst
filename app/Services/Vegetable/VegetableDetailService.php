<?php

namespace App\Services\Vegetable;

use App\Enums\Analytics\VegetableViewerRole;
use App\Enums\Post\PostType;
use App\Enums\PostItemStatus;
use App\Models\Vegetable\Vegetable;
use Illuminate\Support\Facades\DB;

class VegetableDetailService
{
    public function __construct(
        private VegetableActivityService $activityService,
        private VegetableCalendarService $calendarService,
        private VegetableAnalyticsService $analyticsService,
    ) {}

    public function summary(): array
    {
        return [
            'total_vegetables' => Vegetable::distinct('vegetable_name')->count('vegetable_name'),
        ];
    }

    public function show(
        Vegetable $vegetable,
        int $year,
        int $month,
        VegetableViewerRole $role,
        int $activityOffset = 0,
    ): Vegetable {
        $counts = $vegetable->postItems()
            ->ongoing()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->whereNull('posts.deleted_at')
            ->selectRaw("
                SUM(CASE WHEN posts.type = 'supply' THEN 1 ELSE 0 END) as supply_count,
                SUM(CASE WHEN posts.type = 'demand' THEN 1 ELSE 0 END) as demand_count
            ")
            ->first();

        $vegetable->supply_count = (int) ($counts->supply_count ?? 0);
        $vegetable->demand_count = (int) ($counts->demand_count ?? 0);
        $vegetable->supply_municipalities = $this->resolveSupplyMunicipalities($vegetable->id);

        // Anchored to now — never offset. This is what analytics/forecast run on.
        $extendedHistory = $this->activityService->buildMonthlyActivity($vegetable->id, months: 60);
        $recentMonthlyActivity = array_slice($extendedHistory, -12);

        // Independently offsettable — this is what the chart actually renders.
        $vegetable->monthly_activity = $this->activityService->buildMonthlyActivity(
            $vegetable->id,
            months: $this->resolveActivityWindowMonths($activityOffset),
            endOffsetMonths: $activityOffset,
        );
        $vegetable->activity_offset = $activityOffset;
        $vegetable->activity_max_offset = VegetableActivityService::MAX_OFFSET_MONTHS;

        $vegetable->vegetable_calendar = $this->calendarService->buildForMonth($vegetable->id, $year, $month);

        ['analytics' => $vegetable->analytics, 'forecast' => $vegetable->forecast] =
            $this->analyticsService->compute($recentMonthlyActivity, $role, $extendedHistory);

        return $vegetable;
    }

    /**
     * Offset 0 is the fixed 6-month default window (free tier). Every
     * historical page after that is 12 months, contiguous with whatever
     * came before it — offset 6 covers months-back 6-17, offset 18 covers
     * 18-29, and so on. The final page is clamped to whatever's left under
     * VegetableActivityService::TOTAL_HISTORY_MONTHS so the 5-year cap lands
     * exactly on a boundary instead of overshooting it.
     *
     * Coverage check (do this arithmetic yourself before changing any of
     * these numbers): 6 + 12 + 12 + 12 + 12 + 6 = 60 — no gaps, no overlap.
     */
    private function resolveActivityWindowMonths(int $activityOffset): int
    {
        if ($activityOffset === 0) {
            return 6;
        }

        return min(12, VegetableActivityService::TOTAL_HISTORY_MONTHS - $activityOffset);
    }

    private function resolveSupplyMunicipalities(int $vegetableId): array
    {
        return DB::table('post_items')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('farmer_profiles', 'users.id', '=', 'farmer_profiles.user_id')
            ->join('municipalities', 'farmer_profiles.municipality_id', '=', 'municipalities.id')
            ->where('post_items.vegetable_id', $vegetableId)
            ->where('posts.type', PostType::Supply->value)
            ->where('post_items.status', PostItemStatus::Ongoing->value)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->groupBy('municipalities.id', 'municipalities.name')
            ->select('municipalities.name as municipality_name', DB::raw('SUM(post_items.quantity_kg) as total_kg'))
            ->get()
            ->map(fn ($row) => ['name' => $row->municipality_name, 'total_kg' => (float) $row->total_kg])
            ->sortByDesc('total_kg')
            ->values()
            ->toArray();
    }
}
