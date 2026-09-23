<?php

namespace App\Console\Commands;

use App\Enums\Analytics\ImbalanceBand;
use App\Models\Vegetable\VegetableWatch;
use App\Notifications\VegetableOutlookAlert;
use App\Services\Vegetable\VegetableActivityService;
use App\Services\Vegetable\VegetableAnalyticsService;
use Illuminate\Console\Command;

class EvaluateVegetableWatchesCommand extends Command
{
    protected $signature = 'vegetable-watches:evaluate';

    protected $description = 'Re-evaluate forecasted supply/demand outlook for every watched vegetable and notify on band changes.';

    public function handle(VegetableActivityService $activity, VegetableAnalyticsService $analytics): int
    {
        $notified = 0;

        VegetableWatch::query()
            ->select('vegetable_id')
            ->distinct()
            ->chunkById(50, function ($vegetables) use ($activity, $analytics, &$notified) {
                foreach ($vegetables as $row) {
                    $history = $activity->buildMonthlyActivity($row->vegetable_id, months: 60);
                    $forecastDto = $analytics->computeForecastOnly($history, $history);

                    VegetableWatch::where('vegetable_id', $row->vegetable_id)
                        ->chunkById(200, function ($watches) use ($forecastDto, $analytics, &$notified) {
                            foreach ($watches as $watch) {
                                $this->evaluateWatch($watch, $forecastDto, $analytics, $notified);
                            }
                        });
                }
            }, 'vegetable_id', 'vegetable_id');

        $this->info("Sent {$notified} outlook alert(s).");

        return self::SUCCESS;
    }

    private function evaluateWatch($watch, $forecastDto, VegetableAnalyticsService $analytics, int &$notified): void
    {
        $previousBand = $watch->last_notified_band
            ? ImbalanceBand::from($watch->last_notified_band)
            : null;

        $outlook = $analytics->forecastOutlook(
            $forecastDto->forecast,
            $forecastDto->forecast_confidence,
            $previousBand,
        );

        if ($outlook === null || $outlook['band'] === ImbalanceBand::Balanced) {
            $watch->update(['last_evaluated_at' => now()]);

            return;
        }

        if ($outlook['band']->value === $watch->last_notified_band) {
            $watch->update(['last_evaluated_at' => now()]);

            return;
        }

        $watch->user->notify(new VegetableOutlookAlert($watch->vegetable, $outlook));

        $watch->update([
            'last_notified_band' => $outlook['band']->value,
            'last_evaluated_at' => now(),
        ]);

        $notified++;
    }
}
