<?php

namespace App\Http\Controllers\Shared;

use App\Data\Vegetable\VegetableIndexData;
use App\Http\Controllers\Concerns\RendersVegetableShow;
use App\Http\Controllers\Controller;
use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;
use App\Services\Vegetable\VegetableDetailService;
use App\Services\Vegetable\VegetableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VegetableController extends Controller
{
    use RendersVegetableShow;

    public function __construct(
        private readonly VegetableService $vegetableService,
        private readonly VegetableDetailService $vegetableDetailService,
    ) {}

    public function index(Request $request): Response
    {
        $categoryId = $request->query('category_id');

        return Inertia::render('shared/vegetables/Index', [
            'vegetables' => Inertia::defer(fn () => VegetableIndexData::collect(
                $this->vegetableService->paginated(
                    search: $request->query('search'),
                    categoryId: $categoryId,
                )->paginate(12)->withQueryString(),
            )),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => $request->query('search', null),
                'category_id' => $categoryId ? (int) $categoryId : null,
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        return response()->json($this->vegetableService->options());
    }

    public function show(Request $request, Vegetable $vegetable): Response
    {
        return $this->renderVegetableShow($request, $vegetable, $this->vegetableDetailService);
    }
}
