<?php

namespace App\Services\Vegetable\Analytics;

use App\Enums\Analytics\ImbalanceBand;

class ImbalanceBandClassifier
{
    private const float OVERSUPPLY_THRESHOLD = 0.20;

    private const float UNDERSUPPLY_THRESHOLD = -0.20;

    /** Schmitt-trigger margin — see class docblock on OutlookEvaluator for why. */
    private const float HYSTERESIS_MARGIN = 0.05;

    public function classify(float $ratio): ImbalanceBand
    {
        return match (true) {
            $ratio > self::OVERSUPPLY_THRESHOLD => ImbalanceBand::Oversupply,
            $ratio < self::UNDERSUPPLY_THRESHOLD => ImbalanceBand::Undersupply,
            default => ImbalanceBand::Balanced,
        };
    }

    public function classifyWithHysteresis(float $ratio, ?ImbalanceBand $previous): ImbalanceBand
    {
        $upper = self::OVERSUPPLY_THRESHOLD - ($previous === ImbalanceBand::Oversupply ? self::HYSTERESIS_MARGIN : 0.0);
        $lower = self::UNDERSUPPLY_THRESHOLD + ($previous === ImbalanceBand::Undersupply ? self::HYSTERESIS_MARGIN : 0.0);

        return match (true) {
            $ratio > $upper => ImbalanceBand::Oversupply,
            $ratio < $lower => ImbalanceBand::Undersupply,
            default => ImbalanceBand::Balanced,
        };
    }

    public function ratioFromVolumes(float $supplyKg, float $demandKg): float
    {
        return round(($supplyKg - $demandKg) / max($demandKg, 1.0), 4);
    }
}
