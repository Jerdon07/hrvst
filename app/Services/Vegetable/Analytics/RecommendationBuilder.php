<?php

namespace App\Services\Vegetable\Analytics;

use App\DTOs\Vegetable\VegetableRecommendationDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\RecommendationSeverity;
use App\Enums\Analytics\VegetableViewerRole;

class RecommendationBuilder
{
    private const float LOW_FULFILLMENT_THRESHOLD = 0.50;

    private const float SUPPLY_DECLINE_THRESHOLD = -20.0;

    public function fulfillmentRate(array $months, string $type): ?float
    {
        $fulfilled = (float) array_sum(array_map(fn (array $m) => $m["{$type}_fulfilled_kg"], $months));
        $expired = (float) array_sum(array_map(fn (array $m) => $m["{$type}_expired_kg"], $months));
        $total = $fulfilled + $expired;

        return $total > 0.0 ? round($fulfilled / $total, 4) : null;
    }

    /** @return array{?float, ?float} */
    public function volumeMonthOverMonth(array $monthlyActivity): array
    {
        $count = count($monthlyActivity);
        $lastMonth = $monthlyActivity[$count - 2] ?? null;
        $prevMonth = $monthlyActivity[$count - 3] ?? null;

        if ($lastMonth === null || $prevMonth === null) {
            return [null, null];
        }

        $lastSupply = $lastMonth['supply_fulfilled_kg'] + $lastMonth['supply_expired_kg'];
        $prevSupply = $prevMonth['supply_fulfilled_kg'] + $prevMonth['supply_expired_kg'];
        $lastDemand = $lastMonth['demand_fulfilled_kg'] + $lastMonth['demand_expired_kg'];
        $prevDemand = $prevMonth['demand_fulfilled_kg'] + $prevMonth['demand_expired_kg'];

        return [
            $prevSupply > 0.0 ? round((($lastSupply - $prevSupply) / $prevSupply) * 100, 2) : null,
            $prevDemand > 0.0 ? round((($lastDemand - $prevDemand) / $prevDemand) * 100, 2) : null,
        ];
    }

    /** @return VegetableRecommendationDTO[] */
    public function build(
        ImbalanceBand $band,
        ?float $supplyFulfillment,
        ?float $demandFulfillment,
        ?float $supplyMomPct,
        VegetableViewerRole $role,
    ): array {
        $recs = [];

        if ($band === ImbalanceBand::Oversupply) {
            $body = match ($role) {
                VegetableViewerRole::Admin => 'Supply is currently exceeding dealer demand. Consider highlighting this vegetable to dealers or slowing farmer intake.',
                VegetableViewerRole::Farmer => 'This vegetable is currently oversupplied. Consider delaying your next harvest posting or choosing an under-demanded slot.',
                VegetableViewerRole::Dealer => 'There is surplus supply for this vegetable right now — a good time to increase your order to help absorb it before it expires.',
            };
            $recs[] = new VegetableRecommendationDTO(RecommendationSeverity::Warning, 'oversupply_opportunity', 'Unmatched Farmer Supply', $body);
        }

        if ($band === ImbalanceBand::Undersupply) {
            $body = match ($role) {
                VegetableViewerRole::Admin => 'Dealer demand is outpacing available supply. Consider prompting more farmers to post.',
                VegetableViewerRole::Farmer => 'Buyers are actively looking for this vegetable. Good time to schedule your available harvest.',
                VegetableViewerRole::Dealer => 'Supply is currently scarce for this vegetable. Expect longer wait times, or consider adjusting your demanded quantity.',
            };
            $recs[] = new VegetableRecommendationDTO(RecommendationSeverity::Warning, 'supply_opportunity', 'Unfulfilled Dealer Demand', $body);
        }

        if ($supplyFulfillment !== null && $supplyFulfillment < self::LOW_FULFILLMENT_THRESHOLD && $role !== VegetableViewerRole::Dealer) {
            $expiredPct = (int) round((1 - $supplyFulfillment) * 100);
            $recs[] = new VegetableRecommendationDTO(
                RecommendationSeverity::Warning, 'high_supply_expiry_rate', 'High Supply Expiry Rate',
                "{$expiredPct}% of supply posts over the last 3 months expired without a match. This typically indicates a delivery timing mismatch with buyers.",
            );
        }

        if ($demandFulfillment !== null && $demandFulfillment < self::LOW_FULFILLMENT_THRESHOLD && $role !== VegetableViewerRole::Farmer) {
            $expiredPct = (int) round((1 - $demandFulfillment) * 100);
            $recs[] = new VegetableRecommendationDTO(
                RecommendationSeverity::Warning, 'high_demand_expiry_rate', 'Low Demand Fulfillment',
                "{$expiredPct}% of demand posts expired unfulfilled over the last 3 months. Dealers are not finding adequate supply to match their requirements.",
            );
        }

        if ($supplyMomPct !== null && $supplyMomPct < self::SUPPLY_DECLINE_THRESHOLD) {
            $dropPct = (int) round(abs($supplyMomPct));
            $recs[] = new VegetableRecommendationDTO(
                RecommendationSeverity::Info, 'declining_supply_volume', 'Supply Volume Declining',
                "Supply volume dropped {$dropPct}% compared to last month. Monitor whether this is seasonal or signals a structural reduction.",
            );
        }

        usort($recs, fn ($a, $b) => $this->severityOrder($a->severity) <=> $this->severityOrder($b->severity));

        return $recs;
    }

    private function severityOrder(RecommendationSeverity $severity): int
    {
        return match ($severity) {
            RecommendationSeverity::Critical => 0,
            RecommendationSeverity::Warning => 1,
            RecommendationSeverity::Info => 2,
        };
    }
}
