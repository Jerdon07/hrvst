<?php

namespace App\Services\Vegetable;

use App\DTOs\Vegetable\VegetableAnalyticsDTO;
use App\DTOs\Vegetable\VegetableForecastDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\VegetableViewerRole;
use App\Services\Vegetable\Analytics\ExpectedBalanceEstimator;
use App\Services\Vegetable\Analytics\ImbalanceBandClassifier;
use App\Services\Vegetable\Analytics\OutlookEvaluator;
use App\Services\Vegetable\Analytics\RecommendationBuilder;
use App\Services\Vegetable\Analytics\SeasonalForecaster;

class VegetableAnalyticsService
{
    public function __construct(
        private ImbalanceBandClassifier $classifier,
        private SeasonalForecaster $forecaster,
        private ExpectedBalanceEstimator $balanceEstimator,
        private RecommendationBuilder $recommendationBuilder,
        private OutlookEvaluator $outlookEvaluator,
    ) {}

    public function compute(array $monthlyActivity, VegetableViewerRole $role, array $extendedHistory = []): array
    {
        $completeMonths = array_slice($monthlyActivity, -4, 3);
        $ratio = $this->computeImbalanceRatio($completeMonths);
        $band = $this->classifier->classify($ratio);
        $supplyFulfillment = $this->recommendationBuilder->fulfillmentRate($completeMonths, 'supply');
        $demandFulfillment = $this->recommendationBuilder->fulfillmentRate($completeMonths, 'demand');
        [$supplyMomPct, $demandMomPct] = $this->recommendationBuilder->volumeMonthOverMonth($monthlyActivity);

        return [
            'analytics' => new VegetableAnalyticsDTO(
                supply_demand_ratio: $ratio,
                imbalance_band: $band,
                supply_fulfillment_rate: $supplyFulfillment,
                demand_fulfillment_rate: $demandFulfillment,
                supply_volume_mom_pct: $supplyMomPct,
                demand_volume_mom_pct: $demandMomPct,
                recommendations: $this->recommendationBuilder->build($band, $supplyFulfillment, $demandFulfillment, $supplyMomPct, $role),
                expected_balance: $this->balanceEstimator->estimate($extendedHistory ?: $monthlyActivity),
            ),
            'forecast' => $this->computeForecastOnly($monthlyActivity, $extendedHistory),
        ];
    }

    public function computeForecastOnly(array $monthlyActivity, array $extendedHistory = []): VegetableForecastDTO
    {
        $forecastSource = $extendedHistory ?: $monthlyActivity;
        $monthsObserved = $this->forecaster->countRealMonths($forecastSource);

        return new VegetableForecastDTO(
            months_of_history: $monthsObserved,
            forecast_confidence: $this->forecaster->confidence($monthsObserved),
            forecast: $this->forecaster->forecast($forecastSource),
        );
    }

    public function forecastOutlook(array $forecast, string $forecastConfidence, ?ImbalanceBand $previousBand): ?array
    {
        return $this->outlookEvaluator->evaluate($forecast, $forecastConfidence, $previousBand);
    }

    private function computeImbalanceRatio(array $months): float
    {
        if (empty($months)) {
            return 0.0;
        }

        $totalSupply = array_sum(array_map(fn (array $m) => $m['supply_fulfilled_kg'] + $m['supply_expired_kg'], $months));
        $totalDemand = array_sum(array_map(fn (array $m) => $m['demand_fulfilled_kg'] + $m['demand_expired_kg'], $months));
        $count = count($months);

        return $this->classifier->ratioFromVolumes($totalSupply / $count, $totalDemand / $count);
    }
}