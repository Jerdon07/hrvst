<?php

namespace App\Http\Controllers\Shared;

use App\Data\Profile\DealerData;
use App\Data\Profile\FarmerData;
use App\Data\Profile\UserData;
use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\DealerService;
use App\Services\Admin\FarmerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private FarmerService $farmerService,
        private DealerService $dealerService,
    ) {}

    public function show(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $profileType = match (true) {
            $user->farmerProfile !== null => 'farmer',
            $user->dealerProfile !== null => 'dealer',
            default => 'basic',
        };

        $hasAnalyticsAccess = SubscriptionFeature::hasAccessFor($request->user());

        return Inertia::render('shared/users/Show', [
            'profileType' => $profileType,
            // Non-deferred so the page title/breadcrumb render immediately,
            // without waiting on the (potentially slower) profile lookup.
            'meta' => [
                'userId' => $user->id,
                'name' => $user->name,
            ],
            'profile' => Inertia::defer(fn () => match ($profileType) {
                'farmer' => FarmerData::from($this->farmerService->show($user->farmerProfile, $hasAnalyticsAccess)),
                'dealer' => DealerData::from($this->dealerService->show($user->dealerProfile, $hasAnalyticsAccess)),
                default => UserData::fromModel($user),
            }),
        ]);
    }
}