<?php

namespace App\Data\Post;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableOverlapData extends Data
{
    public function __construct(
        public int $post_item_id,
        public int $vegetable_id,
        public float $total_kg,
        public float $total_supplies_kg,
        public float $total_demands_kg,
        /** @var OverlapPosterData[] */
        #[DataCollectionOf(OverlapPosterData::class)]
        public array $posters,
        /** @var OverlapPosterData[] */
        #[DataCollectionOf(OverlapPosterData::class)]
        public array $supply_posters,
        /** @var OverlapPosterData[] */
        #[DataCollectionOf(OverlapPosterData::class)]
        public array $demand_posters,
    ) {}
}