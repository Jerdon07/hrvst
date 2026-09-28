<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ReadsSortParameters
{
    /**
     * @return array{sort: ?string, direction: ?string}
     */
    protected function sortParameters(Request $request): array
    {
        $sort = $request->query('sort');
        $direction = $request->query('direction');

        return [
            'sort' => is_string($sort) && $sort !== '' ? $sort : null,
            'direction' => in_array($direction, ['asc', 'desc'], true) ? $direction : null,
        ];
    }
}