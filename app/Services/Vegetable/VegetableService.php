<?php

namespace App\Services\Vegetable;

use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class VegetableService
{
    public function __construct(private VegetableOptionsBuilder $optionsBuilder) {}

    public function paginated(
        ?string $categoryId = null,
        ?string $search = null,
    ): Builder {
        return Vegetable::query()
            ->with('category')
            ->withCount([
                'postItems as supply_count' => fn (Builder $q) => $q->ongoing()->whereHas('post', fn (Builder $p) => $p->supply()),
                'postItems as demand_count' => fn (Builder $q) => $q->ongoing()->whereHas('post', fn (Builder $p) => $p->demand()),
            ])
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->search($search)
            ->orderBy('vegetable_name')
            ->orderByRaw('variety_name IS NULL, variety_name');
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
}
