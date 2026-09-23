<?php

use App\Models\Vegetable\Vegetable;
use App\Services\Vegetable\VegetableActivityService;
use App\Services\Vegetable\VegetableWasteAnalyticsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Array cache store persists across tests in-process; this service keys its
    // cache off column+limit, which collides across test cases without a flush.
    Cache::flush();
    $this->service = new VegetableWasteAnalyticsService(new VegetableActivityService);
});

function insertMonthlyStat(Vegetable $vegetable, string $periodDate, array $overrides = []): void
{
    DB::table('vegetable_monthly_stats')->insert(array_merge([
        'vegetable_id' => $vegetable->id,
        'period_date' => $periodDate,
        'supply_expired_kg' => 0,
        'supply_fulfilled_kg' => 0,
        'demand_expired_kg' => 0,
        'demand_fulfilled_kg' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

function seedFlatWasteHistory(Vegetable $vegetable, string $column, float $value, int $months = 24): void
{
    for ($i = $months; $i >= 1; $i--) {
        insertMonthlyStat($vegetable, now()->startOfMonth()->subMonths($i)->toDateString(), [$column => $value]);
    }
}

describe('forecast (topWastedDemand / topWastedSupply)', function () {
    it('returns nothing when a vegetable has fewer than 3 months of real history', function () {
        $vegetable = createVegetable();
        insertMonthlyStat($vegetable, now()->startOfMonth()->subMonths(1)->toDateString(), ['demand_expired_kg' => 50]);
        insertMonthlyStat($vegetable, now()->startOfMonth()->subMonths(2)->toDateString(), ['demand_expired_kg' => 50]);

        expect($this->service->topWastedDemand())->toBeEmpty();
    });

    it('forecasts a flat, trendless history at a positive level', function () {
        $vegetable = createVegetable();
        seedFlatWasteHistory($vegetable, 'demand_expired_kg', 40.0);

        $result = $this->service->topWastedDemand();

        expect($result)->toHaveCount(1)
            ->and($result[0]['id'])->toBe($vegetable->id)
            ->and($result[0]['value_kg'])->toBeGreaterThan(0.0);
    });

    it('excludes vegetables with zero forecasted waste', function () {
        seedFlatWasteHistory(createVegetable(), 'demand_expired_kg', 0.0);

        expect($this->service->topWastedDemand())->toBeEmpty();
    });

    it('orders results by forecasted waste descending', function () {
        $low = createVegetable();
        $high = createVegetable();

        seedFlatWasteHistory($low, 'supply_expired_kg', 10.0);
        seedFlatWasteHistory($high, 'supply_expired_kg', 100.0);

        $result = $this->service->topWastedSupply();

        expect($result[0]['id'])->toBe($high->id)
            ->and($result[1]['id'])->toBe($low->id);
    });

    it('respects the requested limit', function () {
        foreach (range(1, 5) as $i) {
            seedFlatWasteHistory(createVegetable(), 'demand_expired_kg', 10.0 * $i);
        }

        expect($this->service->topWastedDemand(limit: 2))->toHaveCount(2);
    });

    it('excludes the current, still-partial month from the forecast basis', function () {
        $vegetable = createVegetable();
        seedFlatWasteHistory($vegetable, 'demand_expired_kg', 40.0);
        insertMonthlyStat($vegetable, now()->startOfMonth()->toDateString(), ['demand_expired_kg' => 999999]);

        expect($this->service->topWastedDemand()[0]['value_kg'])->toBeLessThan(1000.0);
    });
});

describe('stability (mostStableWastedDemand / mostStableWastedSupply)', function () {
    it('excludes a vegetable below the minimum months required for a stability read', function () {
        $thin = createVegetable();
        insertMonthlyStat($thin, now()->startOfMonth()->subMonths(1)->toDateString(), ['demand_expired_kg' => 50]);
        insertMonthlyStat($thin, now()->startOfMonth()->subMonths(2)->toDateString(), ['demand_expired_kg' => 50]);

        foreach (range(1, 4) as $i) {
            seedFlatWasteHistory(createVegetable(), 'demand_expired_kg', 50.0);
        }

        expect(collect($this->service->mostStableWastedDemand())->pluck('id'))->not->toContain($thin->id);
    });

    it('returns nothing when fewer than 4 vegetables have any qualifying history', function () {
        seedFlatWasteHistory(createVegetable(), 'demand_expired_kg', 50.0);

        expect($this->service->mostStableWastedDemand())->toBeEmpty();
    });

    it('ranks a perfectly flat vegetable above a volatile one once both pass the mean floor', function () {
        $flat = createVegetable();
        $volatile = createVegetable();

        seedFlatWasteHistory($flat, 'demand_expired_kg', 100.0);

        for ($i = 24; $i >= 1; $i--) {
            insertMonthlyStat($volatile, now()->startOfMonth()->subMonths($i)->toDateString(), [
                'demand_expired_kg' => $i % 2 === 0 ? 200.0 : 10.0,
            ]);
        }

        // Pad the candidate pool so the top-quartile mean filter doesn't exclude either.
        seedFlatWasteHistory(createVegetable(), 'demand_expired_kg', 100.0);
        seedFlatWasteHistory(createVegetable(), 'demand_expired_kg', 100.0);

        $ids = collect($this->service->mostStableWastedDemand())->pluck('id')->all();

        if (in_array($flat->id, $ids, true) && in_array($volatile->id, $ids, true)) {
            expect(array_search($flat->id, $ids, true))->toBeLessThan(array_search($volatile->id, $ids, true));
        } else {
            // At minimum the zero-variance vegetable must clear the floor.
            expect($ids)->toContain($flat->id);
        }
    });

    it('excludes the current, partial month from the stability calculation', function () {
        $vegetable = createVegetable();
        seedFlatWasteHistory($vegetable, 'demand_expired_kg', 50.0);
        insertMonthlyStat($vegetable, now()->startOfMonth()->toDateString(), ['demand_expired_kg' => 999999]);

        foreach (range(1, 4) as $i) {
            seedFlatWasteHistory(createVegetable(), 'demand_expired_kg', 50.0);
        }

        $mine = collect($this->service->mostStableWastedDemand())->firstWhere('id', $vegetable->id);

        expect($mine)->not->toBeNull()
            ->and($mine['value_kg'])->toBeLessThan(1000.0);
    });
});
