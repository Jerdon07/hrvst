<?php

use App\Enums\Analytics\ImbalanceBand;
use App\Services\Vegetable\Analytics\ExpectedBalanceEstimator;
use App\Services\Vegetable\Analytics\ImbalanceBandClassifier;

beforeEach(function () {
    $this->estimator = new ExpectedBalanceEstimator(new ImbalanceBandClassifier);
});

function balanceMonth(string $monthKey, float $supply, float $demand, bool $hasData = true): array
{
    return [
        'month' => $monthKey,
        'has_data' => $hasData,
        'supply_fulfilled_kg' => $supply,
        'supply_expired_kg' => 0.0,
        'demand_fulfilled_kg' => $demand,
        'demand_expired_kg' => 0.0,
    ];
}

it('returns a balanced "not enough data" result for empty history', function () {
    $result = $this->estimator->estimate([]);

    expect($result->band)->toBe(ImbalanceBand::Balanced->value)
        ->and($result->explanation)->toBe('Not enough data yet.')
        ->and($result->computation)->toBeNull();
});

it('uses the exact same calendar month from last year when it has data', function () {
    $lastYearKey = now()->startOfMonth()->subYear()->format('Y-m');

    $result = $this->estimator->estimate([balanceMonth($lastYearKey, 150.0, 100.0)]);

    expect($result->band)->toBe(ImbalanceBand::Oversupply->value)
        ->and($result->computation->supply_kg)->toBe(150.0)
        ->and($result->computation->demand_kg)->toBe(100.0)
        ->and($result->computation->diff_pct)->toBe(50.0)
        ->and($result->explanation)->toContain('last year');
});

it('ignores a last-year row with no real data and falls back to the trailing average', function () {
    $lastYearKey = now()->startOfMonth()->subYear()->format('Y-m');
    $currentMonthKey = now()->format('Y-m');

    $history = [
        balanceMonth($lastYearKey, 999.0, 1.0, hasData: false),
        balanceMonth(now()->startOfMonth()->subMonths(1)->format('Y-m'), 100.0, 100.0),
        balanceMonth(now()->startOfMonth()->subMonths(2)->format('Y-m'), 100.0, 100.0),
        balanceMonth(now()->startOfMonth()->subMonths(3)->format('Y-m'), 100.0, 100.0),
        balanceMonth($currentMonthKey, 5000.0, 1.0), // must be excluded as partial
    ];

    $result = $this->estimator->estimate($history);

    expect($result->band)->toBe(ImbalanceBand::Balanced->value)
        ->and($result->computation->supply_kg)->toBe(100.0)
        ->and($result->computation->demand_kg)->toBe(100.0)
        ->and($result->explanation)->toContain('average');
});

it('returns "not enough data" when only the current (partial) month exists', function () {
    $result = $this->estimator->estimate([balanceMonth(now()->format('Y-m'), 100.0, 50.0)]);

    expect($result->explanation)->toBe('Not enough data yet.')
        ->and($result->computation)->toBeNull();
});

it('classifies undersupply from the trailing average when demand outpaces supply', function () {
    $history = [
        balanceMonth(now()->startOfMonth()->subMonths(1)->format('Y-m'), 50.0, 100.0),
        balanceMonth(now()->startOfMonth()->subMonths(2)->format('Y-m'), 50.0, 100.0),
    ];

    expect($this->estimator->estimate($history)->band)->toBe(ImbalanceBand::Undersupply->value);
});

it('leaves diff_pct null when trailing demand is zero, since the percentage is undefined', function () {
    $history = [balanceMonth(now()->startOfMonth()->subMonths(1)->format('Y-m'), 50.0, 0.0)];

    expect($this->estimator->estimate($history)->computation->diff_pct)->toBeNull();
});
