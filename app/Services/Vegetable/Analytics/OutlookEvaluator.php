<?php

namespace App\Services\Vegetable\Analytics;

use App\Enums\Analytics\ImbalanceBand;

class OutlookEvaluator
{
    public function __construct(private ImbalanceBandClassifier $classifier) {}

    public function evaluate(array $forecast, string $forecastConfidence, ?ImbalanceBand $previousBand): ?array
    {
        if ($forecastConfidence === 'insufficient' || empty($forecast)) {
            return null;
        }

        $ratios = array_map(fn (array $month) => $this->monthRatio($month), $forecast);
        $band = $this->classifier->classifyWithHysteresis($ratios[0], $previousBand);

        if ($band === ImbalanceBand::Balanced) {
            return ['band' => $band];
        }

        $durationMonths = 0;
        foreach ($ratios as $ratio) {
            if ($this->classifier->classifyWithHysteresis($ratio, $band) !== $band) {
                break;
            }
            $durationMonths++;
        }

        return [
            'band' => $band,
            'starts_in_months' => 1,
            'duration_months' => $durationMonths,
            'label' => $this->outlookLabel($band, 1, $durationMonths),
        ];
    }

    private function monthRatio(array $month): float
    {
        $supply = $month['supply_fulfilled_kg'] + $month['supply_expired_kg'];
        $demand = $month['demand_fulfilled_kg'] + $month['demand_expired_kg'];

        return ($supply - $demand) / max($demand, 1.0);
    }

    private function outlookLabel(ImbalanceBand $band, int $startsIn, int $duration): string
    {
        $when = $startsIn === 1 ? 'next month' : "in the next {$startsIn} months";
        $span = $duration > 1 ? " for about {$duration} months" : '';

        return $band === ImbalanceBand::Oversupply
            ? "Expected to be oversupplied {$when}{$span}."
            : "Expected shortage {$when}{$span}.";
    }
}