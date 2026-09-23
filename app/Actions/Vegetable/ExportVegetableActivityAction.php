<?php

namespace App\Actions\Vegetable;

use App\Models\Vegetable\Vegetable;
use App\Services\Vegetable\VegetableActivityService;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportVegetableActivityAction
{
    public function __construct(private VegetableActivityService $activityService) {}

    public function handle(Vegetable $vegetable): StreamedResponse
    {
        $rows = $this->activityService->buildMonthlyActivity($vegetable->id, months: 24);
        $filename = Str::slug($vegetable->display_name).'-activity.csv';

        return response()->streamDownload(function () use ($rows, $vegetable) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Vegetable', $vegetable->display_name]);
            fputcsv($out, ['Month', 'Supply Fulfilled (kg)', 'Supply Expired (kg)', 'Demand Fulfilled (kg)', 'Demand Expired (kg)']);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['label'],
                    $row['supply_fulfilled_kg'],
                    $row['supply_expired_kg'],
                    $row['demand_fulfilled_kg'],
                    $row['demand_expired_kg'],
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
