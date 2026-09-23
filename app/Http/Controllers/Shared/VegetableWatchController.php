<?php

namespace App\Http\Controllers\Shared;

use App\Enums\Analytics\VegetableViewerRole;
use App\Http\Controllers\Controller;
use App\Models\Vegetable\Vegetable;
use App\Models\Vegetable\VegetableWatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VegetableWatchController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('shared/vegetables/Watches', [
            'watches' => Inertia::defer(fn () => $request->user()
                ->watches()
                ->with('vegetable.category')
                ->latest()
                ->get()
                ->map(fn ($watch) => [
                    'id' => $watch->id,
                    'vegetable_id' => $watch->vegetable_id,
                    'vegetable_name' => $watch->vegetable->display_name,
                    'image_url' => $watch->vegetable->image_url,
                    'category' => $watch->vegetable->category->name,
                    'last_notified_band' => $watch->last_notified_band,
                    'last_evaluated_at' => $watch->last_evaluated_at?->diffForHumans(),
                ])
            ),
        ]);
    }

    public function store(Request $request, Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('create', VegetableWatch::class);

        $user = $request->user();

        $role = match (true) {
            $user->hasRole('farmer') => VegetableViewerRole::Farmer,
            $user->hasRole('dealer') => VegetableViewerRole::Dealer,
            default => abort(403, 'Only farmers and dealers can watch vegetables.'),
        };

        VegetableWatch::firstOrCreate(
            ['user_id' => $user->id, 'vegetable_id' => $vegetable->id],
            ['viewer_role' => $role->value],
        );

        return back()->with('flash', ['type' => 'success', 'message' => 'Watching this vegetable.']);
    }

    public function destroy(Request $request, Vegetable $vegetable): RedirectResponse
    {
        $watch = VegetableWatch::where('user_id', $request->user()->id)
            ->where('vegetable_id', $vegetable->id)
            ->firstOrFail();

        Gate::authorize('delete', $watch);

        $watch->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Stopped watching.']);
    }
}
