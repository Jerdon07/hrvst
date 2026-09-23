<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Vegetable\CreateVegetableAction;
use App\Actions\Vegetable\UpdateVegetableAction;
use App\Data\Vegetable\VegetableIndexData;
use App\Http\Controllers\Concerns\RendersVegetableShow;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vegetable\StoreVegetableRequest;
use App\Http\Requests\Vegetable\UpdateVegetableRequest;
use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;
use App\Services\Vegetable\VegetableDetailService;
use App\Services\Vegetable\VegetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    use RendersVegetableShow;

    public function __construct(
        private readonly VegetableService $vegetableService,
        private readonly VegetableDetailService $vegetableDetailService,
        private readonly CreateVegetableAction $createVegetable,
        private readonly UpdateVegetableAction $updateVegetable,
    ) {}

    public function index(Request $request): Response
    {
        $categoryId = $request->query('category_id');

        return Inertia::render('admin/vegetables/Index', [
            'vegetables' => Inertia::defer(fn () => VegetableIndexData::collect(
                $this->vegetableService->paginated(
                    search: $request->query('search'),
                    categoryId: $categoryId,
                )->paginate(20)->withQueryString(),
            )),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->query('search', null),
                'category_id' => $categoryId ? (int) $categoryId : null,
            ],
        ]);
    }

    public function show(Request $request, Vegetable $vegetable): Response
    {
        return $this->renderVegetableShow($request, $vegetable, $this->vegetableDetailService);
    }

    public function store(StoreVegetableRequest $request): RedirectResponse
    {
        Gate::authorize('create', Vegetable::class);

        $this->createVegetable->handle(
            validated: $request->safe()->except('image'),
            image: $request->file('image'),
        );

        return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Vegetable created successfully.']);
    }

    public function update(UpdateVegetableRequest $request, Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('update', $vegetable);

        $this->updateVegetable->handle(
            vegetable: $vegetable,
            validated: $request->safe()->except('image'),
            image: $request->file('image'),
        );

        return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Vegetable updated successfully.']);
    }

    public function destroy(Vegetable $vegetable): RedirectResponse
    {
        Gate::authorize('delete', $vegetable);
        $vegetable->delete();

        return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Vegetable deleted successfully.']);
    }
}
