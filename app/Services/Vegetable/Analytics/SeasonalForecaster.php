<?php

namespace App\Services\Vegetable\Analytics;

use App\Services\Vegetable\PlatformActivityService;
use Illuminate\Support\Collection;

class SeasonalForecaster
{
    private const float TREND_FLOOR = 0.60;

    private const float TREND_CEIL = 1.40;

    private const int MIN_MONTHS_FOR_TREND = 12;

    private const int MIN_MONTHS_FOR_FORECAST = 12;

    private const int CONFIDENCE_ESTABLISHED_MONTHS = 36;

    private const int CONFIDENCE_STRONG_MONTHS = 60;

    private const int FORECAST_HORIZON_MONTHS = 6;

    public function __construct(private PlatformActivityService $platformActivity) {}

    public function confidence(int $monthsObserved): string
    {
        return match (true) {
            $monthsObserved < self::MIN_MONTHS_FOR_FORECAST => 'insufficient',
            $monthsObserved >= self::CONFIDENCE_STRONG_MONTHS => 'strong',
            $monthsObserved >= self::CONFIDENCE_ESTABLISHED_MONTHS => 'established',
            default => 'developing',
        };
    }

    public function countRealMonths(array $history): int
    {
        $currentMonthKey = now()->format('Y-m');

        return count(array_filter(
            $history,
            fn ($e) => $e['month'] !== $currentMonthKey && ($e['has_data'] ?? true),
        ));
    }

    /**
     * 6-month forward forecast derived from up to 5-year seasonal history,
     * normalized by active-user counts so platform growth doesn't masquerade
     * as per-vegetable demand growth. See PlatformActivityService.
     */
    public function forecast(array $history): array
    {
        $currentMonthKey = now()->format('Y-m');

        $realHistory = array_values(array_filter(
            $history,
            fn ($e) => $e['month'] !== $currentMonthKey && ($e['has_data'] ?? true),
        ));

        $realCount = count($realHistory);

        if ($realCount < self::MIN_MONTHS_FOR_FORECAST) {
            return [];
        }

        $activeCounts = $this->platformActivity->monthlyActiveCounts();

        $byCalendarMonth = [];

        foreach ($realHistory as $row) {
            $calMonth = (int) substr($row['month'], 5, 2);
            $year = (int) substr($row['month'], 0, 4);

            [$farmers, $dealers] = $this->activeCountsFor($row['month'], $activeCounts);

            $byCalendarMonth[$calMonth][] = [
                'year' => $year,
                'supply_fulfilled_per_capita' => $row['supply_fulfilled_kg'] / $farmers,
                'supply_expired_per_capita' => $row['supply_expired_kg'] / $farmers,
                'demand_fulfilled_per_capita' => $row['demand_fulfilled_kg'] / $dealers,
                'demand_expired_per_capita' => $row['demand_expired_kg'] / $dealers,
            ];
        }

        $supplyMonthlyGrowth = 1.0;
        $demandMonthlyGrowth = 1.0;

        if ($realCount >= self::MIN_MONTHS_FOR_TREND) {
            $recentSlice = array_slice($realHistory, -6);
            $priorSlice = array_slice($realHistory, $realCount - 12, 6);

            $recentSupply = $this->perCapitaVolumeSum($recentSlice, 'supply', 'active_farmers', $activeCounts);
            $priorSupply = $this->perCapitaVolumeSum($priorSlice, 'supply', 'active_farmers', $activeCounts);
            $recentDemand = $this->perCapitaVolumeSum($recentSlice, 'demand', 'active_dealers', $activeCounts);
            $priorDemand = $this->perCapitaVolumeSum($priorSlice, 'demand', 'active_dealers', $activeCounts);

            $supplyTrend = $priorSupply > 0.0
                ? max(self::TREND_FLOOR, min(self::TREND_CEIL, $recentSupply / $priorSupply))
                : 1.0;
            $demandTrend = $priorDemand > 0.0
                ? max(self::TREND_FLOOR, min(self::TREND_CEIL, $recentDemand / $priorDemand))
                : 1.0;

            $supplyMonthlyGrowth = $supplyTrend ** (1 / 6);
            $demandMonthlyGrowth = $demandTrend ** (1 / 6);
        }

        $latestMonth = end($realHistory)['month'] ?? null;
        [$currentFarmers, $currentDealers] = $latestMonth
            ? $this->activeCountsFor($latestMonth, $activeCounts)
            : [1, 1];

        $forecast = [];
        $weights = [3, 2, 1];

        for ($i = 1; $i <= self::FORECAST_HORIZON_MONTHS; $i++) {
            $futureDate = now()->startOfMonth()->addMonths($i);
            $calMonth = (int) $futureDate->month;
            $entries = $byCalendarMonth[$calMonth] ?? [];

            if (empty($entries)) {
                continue;
            }

            usort($entries, fn ($a, $b) => $b['year'] <=> $a['year']);

            $totalWeight = 0;
            $supplyFulfilledPC = 0.0;
            $supplyExpiredPC = 0.0;
            $demandFulfilledPC = 0.0;
            $demandExpiredPC = 0.0;

            foreach (array_slice($entries, 0, 3) as $idx => $entry) {
                $w = $weights[$idx] ?? 1;
                $totalWeight += $w;
                $supplyFulfilledPC += $entry['supply_fulfilled_per_capita'] * $w;
                $supplyExpiredPC += $entry['supply_expired_per_capita'] * $w;
                $demandFulfilledPC += $entry['demand_fulfilled_per_capita'] * $w;
                $demandExpiredPC += $entry['demand_expired_per_capita'] * $w;
            }

            $supplyFactor = $supplyMonthlyGrowth ** $i;
            $demandFactor = $demandMonthlyGrowth ** $i;

            $forecast[] = [
                'month' => $futureDate->format('Y-m'),
                'label' => $futureDate->format('M Y'),
                'supply_fulfilled_kg' => max(0.0, round(($supplyFulfilledPC / $totalWeight) * $supplyFactor * $currentFarmers, 2)),
                'supply_expired_kg' => max(0.0, round(($supplyExpiredPC / $totalWeight) * $supplyFactor * $currentFarmers, 2)),
                'demand_fulfilled_kg' => max(0.0, round(($demandFulfilledPC / $totalWeight) * $demandFactor * $currentDealers, 2)),
                'demand_expired_kg' => max(0.0, round(($demandExpiredPC / $totalWeight) * $demandFactor * $currentDealers, 2)),
            ];
        }

        return $forecast;
    }

    private function activeCountsFor(string $monthKey, Collection $activeCounts): array
    {
        $row = $activeCounts->get($monthKey);

        return [
            max(1, (int) ($row->active_farmers ?? 1)),
            max(1, (int) ($row->active_dealers ?? 1)),
        ];
    }

    private function perCapitaVolumeSum(array $slice, string $role, string $countColumn, Collection $activeCounts): float
    {
        $sum = 0.0;

        foreach ($slice as $row) {
            $count = max(1, (int) ($activeCounts->get($row['month'])->{$countColumn} ?? 1));
            $sum += ($row["{$role}_fulfilled_kg"] + $row["{$role}_expired_kg"]) / $count;
        }

        return $sum;
    }
}
