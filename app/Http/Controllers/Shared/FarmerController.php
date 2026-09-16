<?php

namespace App\Http\Controllers\Shared;

use App\Data\Profile\FarmerData;
use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Profiles\FarmerProfile;
use App\Services\Admin\FarmerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FarmerController extends Controller
{
    public function __construct(private FarmerService $farmerService) {}

    public function show(Request $request, FarmerProfile $farmer): Response
    {
        Gate::authorize('view', $farmer);

        $hasAnalyticsAccess = SubscriptionFeature::hasAccessFor($request->user());

        return Inertia::render('shared/farmers/Show', [
            'farmer' => Inertia::defer(
                fn () => FarmerData::from($this->farmerService->show($farmer, $hasAnalyticsAccess))
            ),
        ]);
    }
}