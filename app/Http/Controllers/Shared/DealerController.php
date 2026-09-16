<?php

namespace App\Http\Controllers\Shared;

use App\Data\Profile\DealerData;
use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Profiles\DealerProfile;
use App\Services\Admin\DealerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DealerController extends Controller
{
    public function __construct(private DealerService $dealerService) {}

    public function show(Request $request, DealerProfile $dealer): Response
    {
        Gate::authorize('view', $dealer);

        $hasAnalyticsAccess = SubscriptionFeature::hasAccessFor($request->user());

        return Inertia::render('shared/dealers/Show', [
            'dealer' => Inertia::defer(
                fn () => DealerData::from($this->dealerService->show($dealer, $hasAnalyticsAccess))
            ),
        ]);
    }
}