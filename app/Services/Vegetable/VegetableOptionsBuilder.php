<?php

namespace App\Services\Vegetable;

use App\Models\Vegetable\Vegetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VegetableOptionsBuilder
{
    /**
     * @param  (\Closure(Builder): void)|null  $scope  Optional extra constraint, e.g. filter to vegetables with an ongoing supply.
     * @return array<string, array<int, array{id: int, name: string}>>
     */
    public function build(?\Closure $scope = null): array
    {
        return $this->query($scope)
            ->get()
            ->groupBy(fn (Vegetable $v) => $v->category->name)
            ->map(fn (Collection $rows) => $rows
                ->map(fn (Vegetable $v) => ['id' => $v->id, 'name' => $v->display_name])
                ->values()
                ->toArray())
            ->toArray();
    }

    private function query(?\Closure $scope): Builder
    {
        $query = Vegetable::query()
            ->with('category')
            ->orderByRaw('variety_name IS NULL, variety_name')
            ->orderBy('vegetable_name');

        if ($scope) {
            $scope($query);
        }

        return $query;
    }
}
