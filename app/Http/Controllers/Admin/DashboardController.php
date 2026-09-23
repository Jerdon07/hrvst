<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Services\Admin\DashboardService;
use App\Services\Admin\RegistrationTrendService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly RegistrationTrendService $registrationTrendService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $hasAnalyticsAccess = Subscription::hasAccess($request->user(), SubscriptionFeature::AdminAnalytics);

        return Inertia::render('admin/Dashboard', [
            'kpis' => Inertia::defer(fn () => $this->dashboardService->getKPIs()),
            'registrationTrends' => $hasAnalyticsAccess
                ? Inertia::defer(fn () => $this->registrationTrendService->monthly())
                : null,
            'analyticsLocked' => ! $hasAnalyticsAccess,
            'upgradeFeatureLabel' => SubscriptionFeature::AdminAnalytics->label(),
        ]);
    }
}
