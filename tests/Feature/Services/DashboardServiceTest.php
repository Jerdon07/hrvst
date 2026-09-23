<?php

use App\Services\Admin\DashboardService;
use App\Services\Admin\RegistrationTrendService;

function invokeDashboardCalc(string $method, ...$args)
{
    $ref = new ReflectionMethod(DashboardService::class, $method);
    $ref->setAccessible(true);

    return $ref->invoke(null, ...$args);
}

describe('DashboardService percentage/trend math', function () {
    it('returns 0% change when both previous and current are zero', function () {
        expect(invokeDashboardCalc('calculatePercentageChange', 0, 0))->toBe(0.0);
    });

    it('returns 100% change when previous is zero and current is positive', function () {
        expect(invokeDashboardCalc('calculatePercentageChange', 0, 5))->toBe(100.0);
    });

    it('computes a normal percentage increase', function () {
        expect(invokeDashboardCalc('calculatePercentageChange', 10, 15))->toBe(50.0);
    });

    it('computes a normal percentage decrease', function () {
        expect(invokeDashboardCalc('calculatePercentageChange', 10, 5))->toBe(-50.0);
    });

    it('reports "up" when current exceeds previous', function () {
        expect(invokeDashboardCalc('getTrend', 10, 15))->toBe('up');
    });

    it('reports "down" when current is below previous', function () {
        expect(invokeDashboardCalc('getTrend', 15, 10))->toBe('down');
    });

    it('reports "flat" when current equals previous', function () {
        expect(invokeDashboardCalc('getTrend', 10, 10))->toBe('flat');
    });
});

describe('RegistrationTrendService::monthly', function () {
    it('returns exactly 12 months, oldest first, ending on the current month', function () {
        $result = app(RegistrationTrendService::class)->monthly();

        expect($result)->toHaveCount(12)
            ->and($result[11]['month'])->toBe(now()->format('Y-m'));
    });

    it('counts a farmer registered this month in the current bucket', function () {
        createFarmerUser();

        $current = collect(app(RegistrationTrendService::class)->monthly())->firstWhere('month', now()->format('Y-m'));

        expect($current['farmers'])->toBe(1)
            ->and($current['dealers'])->toBe(0);
    });

    it('counts a dealer registered this month in the current bucket', function () {
        createDealerUser();

        $current = collect(app(RegistrationTrendService::class)->monthly())->firstWhere('month', now()->format('Y-m'));

        expect($current['dealers'])->toBe(1);
    });

    it('does not count a farmer profile created before the 12-month window', function () {
        $farmer = createFarmerUser();
        $farmer->farmerProfile->forceFill(['created_at' => now()->subYears(2)])->save();

        $result = app(RegistrationTrendService::class)->monthly();

        expect(array_sum(array_column($result, 'farmers')))->toBe(0);
    });
});
