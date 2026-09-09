<?php

namespace App\Services\Vegetable\Analytics;

use App\DTOs\Vegetable\ExpectedBalanceComputation;
use App\DTOs\Vegetable\ExpectedBalanceDTO;
use App\Enums\Analytics\ImbalanceBand;
use Carbon\Carbon;

class ExpectedBalanceEstimator
{
    private const int TRAILING_WINDOW = 3;

    public function __construct(private ImbalanceBandClassifier $classifier) {}

    public function estimate(array $extendedHistory): ExpectedBalanceDTO
    {
        if (empty($extendedHistory)) {
            return $this->result(ImbalanceBand::Balanced, 'Not enough data yet.');
        }

        $now = now()->startOfMonth();
        $currentMonthKey = $now->format('Y-m');
        $lastYearKey = $now->copy()->subYear()->format('Y-m');
        $lastYearRow = collect($extendedHistory)->firstWhere('month', $lastYearKey);

        if ($lastYearRow && ($lastYearRow['has_data'] ?? true)) {
            $supplyKg = $lastYearRow['supply_fulfilled_kg'] + $lastYearRow['supply_expired_kg'];
            $demandKg = $lastYearRow['demand_fulfilled_kg'] + $lastYearRow['demand_expired_kg'];
            $sourceLabel = Carbon::createFromFormat('Y-m', $lastYearKey)->format('M Y');

            return $this->result(
                $this->classifyBalance($supplyKg, $demandKg),
                "Estimated from {$sourceLabel} last year.",
                $supplyKg,
                $demandKg,
                $sourceLabel,
            );
        }

        $trailing = collect($extendedHistory)
            ->filter(fn ($row) => $row['month'] !== $currentMonthKey && ($row['has_data'] ?? true))
            ->sortByDesc('month')
            ->take(self::TRAILING_WINDOW)
            ->sortBy('month')
            ->values();

        if ($trailing->isEmpty()) {
            return $this->result(ImbalanceBand::Balanced, 'Not enough data yet.');
        }

        $avgSupply = $trailing->avg(fn ($r) => $r['supply_fulfilled_kg'] + $r['supply_expired_kg']);
        $avgDemand = $trailing->avg(fn ($r) => $r['demand_fulfilled_kg'] + $r['demand_expired_kg']);

        $monthLabels = $trailing->pluck('month')
            ->map(fn ($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'))
            ->implode(', ');

        return $this->result(
            $this->classifyBalance($avgSupply, $avgDemand),
            "Estimated from the average of {$monthLabels}.",
            $avgSupply,
            $avgDemand,
            $monthLabels,
        );
    }

    private function classifyBalance(float $supplyKg, float $demandKg): ImbalanceBand
    {
        return $this->classifier->classify($this->classifier->ratioFromVolumes($supplyKg, $demandKg));
    }

    private function result(
        ImbalanceBand $band,
        string $explanation,
        ?float $supplyKg = null,
        ?float $demandKg = null,
        ?string $sourceLabel = null,
    ): ExpectedBalanceDTO {
        return new ExpectedBalanceDTO(
            band: $band->value,
            explanation: $explanation,
            computation: $supplyKg !== null && $demandKg !== null
                ? new ExpectedBalanceComputation(
                    source_label: $sourceLabel ?? '',
                    supply_kg: round($supplyKg, 2),
                    demand_kg: round($demandKg, 2),
                    diff_pct: $demandKg > 0.0 ? round((($supplyKg - $demandKg) / $demandKg) * 100, 1) : null,
                )
                : null,
        );
    }
}