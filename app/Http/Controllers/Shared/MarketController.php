<?php

namespace App\Http\Controllers\Shared;

use App\Data\Market\MarketRowData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\MarketDayRequest;
use App\Services\Vegetable\DailyMarketService;
use Inertia\Inertia;
use Inertia\Response;

class MarketController extends Controller
{
    public function __invoke(MarketDayRequest $request, DailyMarketService $market): Response
    {
        $date = $request->resolvedDate();
        $slot = $request->resolvedSlot();

        return Inertia::render('shared/market/Index', [
            'filters' => [
                'date' => $date->toDateString(),
                'slot' => $slot?->value,
                'bounds' => [
                    'min' => today()->toDateString(),
                    'max' => today()->addMonths(3)->toDateString(),
                ],
            ],
            'rows' => Inertia::defer(fn () => MarketRowData::collect($market->forDay($date, $slot))),
        ]);
    }
}
