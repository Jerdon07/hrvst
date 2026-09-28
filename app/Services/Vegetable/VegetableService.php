<?php

namespace App\Services\Vegetable;

use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class VegetableService
{
    public function __construct(private VegetableOptionsBuilder $optionsBuilder) {}

    public function paginated(
        ?string $categoryId = null,
        ?string $search = null,
        ?string $sort = null,
        ?string $direction = null,
    ): Builder {
        $query = Vegetable::query()
            ->with('category')
            ->withCount([
                'postItems as supply_count' => fn (Builder $q) => $q->ongoing()->whereHas('post', fn (Builder $p) => $p->supply()),
                'postItems as demand_count' => fn (Builder $q) => $q->ongoing()->whereHas('post', fn (Builder $p) => $p->demand()),
            ])
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->search($search);

        $this->applySort($query, $sort, $direction);

        return $query;
    }

    public function summary(): array
    {
        return [
            'total_vegetables' => Vegetable::count(),
        ];
    }

    /**
     * @return array<string, array<int, array{id: int, name: string}>>
     */
    public function options(): array
    {
        return Cache::remember(
            'vegetable_options',
            3600,
            fn () => $this->optionsBuilder->build(),
        );
    }

    private function applySort(Builder $query, ?string $sort, ?string $direction): void
    {
        $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';

        match ($sort) {
            'vegetable' => $query->orderBy('vegetable_name', $direction),
            'category' => $query
                ->orderBy(
                    Category::query()
                        ->select('name')
                        ->whereColumn((new Category)->getTable().'.id', (new Vegetable)->getTable().'.category_id')
                        ->limit(1),
                    $direction,
                )
                ->orderBy('vegetable_name'),
            default => $query->orderBy('vegetable_name'),
        };

        $query->orderByRaw('variety_name IS NULL, variety_name')->orderBy('id');
    }
}