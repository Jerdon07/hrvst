<?php

namespace App\Http\Controllers\Admin;

use App\Data\Profile\DealerData;
use App\Http\Controllers\Controller;
use App\Models\Profiles\DealerProfile;
use App\Services\Admin\DealerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DealerController extends Controller
{
    public function __construct(
        private DealerService $dealerService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DealerProfile::class);

        return Inertia::render('admin/dealers/Index', [
            'summary' => Inertia::defer(fn () => $this->dealerService->summary()),
            'dealers' => Inertia::defer(fn () => DealerData::collect(
                $this->dealerService->paginated(search: $request->query('search', null)),
            )),
            'filters' => ['search' => $request->query('search', null)],
        ]);
    }

    public function details(DealerProfile $dealer): JsonResponse
    {
        Gate::authorize('view', $dealer);

        return response()->json(DealerData::from($this->dealerService->details($dealer)));
    }

    public function destroy(DealerProfile $dealer): RedirectResponse
    {
        Gate::authorize('delete', $dealer);

        $user = $dealer->user;
        $user->roles()->detach();
        $dealer->delete();
        $user->delete();

        return redirect()->route('admin.dealers.index')
            ->with('flash', ['type' => 'success', 'message' => 'Dealer deleted successfully.']);
    }
}