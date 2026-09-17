<?php

namespace App\Http\Controllers\Admin;

use App\Data\Profile\FarmerData;
use App\Enums\Billing\SubscriptionFeature;
use App\Http\Controllers\Controller;
use App\Models\Billing\Subscription;
use App\Models\Profiles\FarmerProfile;
use App\Services\Admin\FarmerMapService;
use App\Services\Admin\FarmerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FarmerController extends Controller
{
    public function __construct(
        private FarmerService $farmerService,
        private FarmerMapService $farmerMapService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        $view = $request->query('view', 'list');

        if ($view === 'map' && ! $this->hasMapAccess($request)) {
            return redirect()->route('billing.show')->with('flash', [
                'type' => 'warning',
                'message' => 'The farmer map requires an active Platform Analytics License subscription.',
            ]);
        }

        return Inertia::render('admin/farmers/Index', [
            'view' => $view,
            'filters' => [
                'search' => $request->query('search', null),
                'municipalities' => $this->farmerMapService->getMunicipalityOptions(),
                'supplies' => $this->farmerMapService->getSupplyOptions(),
            ],
            'mapConfig' => [
                'center' => ['lat' => 16.4137, 'lng' => 120.5896],
                'defaultZoom' => 13,
            ],
            'farmers' => $view === 'list'
                ? Inertia::defer(fn () => FarmerData::collect(
                    $this->farmerService->paginated(
                        perPage: 20,
                        search: $request->query('search', null),
                    )
                ))
                : null,
            'summary' => Inertia::defer(fn () => $this->farmerService->summary()),
        ]);
    }

    public function markers(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', FarmerProfile::class);

        abort_unless($this->hasMapAccess($request), 403, 'The farmer map requires an active Platform Analytics License subscription.');

        $validated = $request->validate([
            'municipality_id' => 'nullable|exists:municipalities,id',
            'vegetable_id' => 'nullable|exists:vegetables,id',
            'bounds' => 'nullable|array',
            'bounds.north' => 'required_with:bounds|numeric',
            'bounds.south' => 'required_with:bounds|numeric',
            'bounds.east' => 'required_with:bounds|numeric',
            'bounds.west' => 'required_with:bounds|numeric',
        ]);

        $farmers = $this->farmerMapService->getFarmersForMap(
            municipalityId: $validated['municipality_id'] ?? null,
            vegetableId: $validated['vegetable_id'] ?? null,
            bounds: $validated['bounds'] ?? null,
        );

        return response()->json(['markers' => $farmers, 'total' => count($farmers)]);
    }

    public function details(FarmerProfile $farmer): JsonResponse
    {
        Gate::authorize('view', $farmer);

        return response()->json(FarmerData::from($this->farmerService->details($farmer)));
    }

    public function destroy(FarmerProfile $farmer): RedirectResponse
    {
        Gate::authorize('delete', $farmer);

        $user = $farmer->user;
        $user->roles()->detach();
        $farmer->delete();
        $user->delete();

        return redirect()->route('admin.farmers.index')
            ->with('flash', ['type' => 'success', 'message' => 'Farmer deleted successfully.']);
    }

    private function hasMapAccess(Request $request): bool
    {
        return Subscription::hasAccess($request->user(), SubscriptionFeature::AdminAnalytics);
    }
}