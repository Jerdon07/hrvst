<?php

use App\Services\Vegetable\Analytics\SeasonalForecaster;
use App\Services\Vegetable\PlatformActivityService;

beforeEach(function () {
    $platformActivity = Mockery::mock(PlatformActivityService::class, function ($mock) {
        $mock->shouldReceive('monthlyActiveCounts')->andReturn(collect());
    });
    $this->forecaster = new SeasonalForecaster($platformActivity);
});

function flatHistory(float $supply, float $demand, int $months = 24): array
{
    $rows = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $date = now()->startOfMonth()->subMonths($i);
        $rows[] = [
            'month' => $date->format('Y-m'),
            'label' => $date->format('M Y'),
            'has_data' => true,
            'supply_fulfilled_kg' => $supply,
            'supply_expired_kg' => 0.0,
            'demand_fulfilled_kg' => $demand,
            'demand_expired_kg' => 0.0,
        ];
    }
    return $rows;
}

it('projects a flat, trendless history forward unchanged', function () {
    $forecast = $this->forecaster->forecast(flatHistory(100.0, 50.0));

    expect($forecast)->not->toBeEmpty();
    foreach ($forecast as $month) {
        expect($month['supply_fulfilled_kg'])->toBe(100.0)
            ->and($month['demand_fulfilled_kg'])->toBe(50.0);
    }
});

it('returns no forecast with less than 12 months of real history', function () {
    expect($this->forecaster->forecast(flatHistory(100.0, 50.0, months: 6)))->toBe([]);
});

it('scales the forecast up when the last 6 months trend higher than the 6 before them', function () {
    $rows = [];
    for ($i = 23; $i >= 0; $i--) {
        $date = now()->startOfMonth()->subMonths($i);
        $rows[] = [
            'month' => $date->format('Y-m'),
            'label' => $date->format('M Y'),
            'has_data' => true,
            'supply_fulfilled_kg' => $i < 6 ? 150.0 : 100.0, // last 6 months up 50%
            'supply_expired_kg' => 0.0,
            'demand_fulfilled_kg' => 0.0,
            'demand_expired_kg' => 0.0,
        ];
    }

    $forecast = $this->forecaster->forecast($rows);

    // trend clamps to 1.4, growth^1 > 1 — forecast must exceed the flat baseline.
    expect($forecast[0]['supply_fulfilled_kg'])->toBeGreaterThan(100.0);
});

it('excludes zero-padded gap months from the seasonal baseline', function () {
    $rows = flatHistory(100.0, 50.0);
    $rows[0]['has_data'] = false;   // corrupt a real month into a "no data" gap
    $rows[0]['supply_fulfilled_kg'] = 0.0;

    $forecast = $this->forecaster->forecast($rows);

    foreach ($forecast as $month) {
        expect($month['supply_fulfilled_kg'])->toBe(100.0); // gap must not drag the average toward 0
    }
});