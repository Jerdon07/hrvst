<?php

namespace App\Data\Market;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MarketRowData extends Data
{
    public function __construct(
        public int $vegetable_id,
        public string $display_name,
        public string $image_url,
        public ?string $category,
        public float $supply_kg,
        public float $demand_kg,
        public float $net_kg,
        public int $supply_posts,
        public int $demand_posts,
    ) {}
}