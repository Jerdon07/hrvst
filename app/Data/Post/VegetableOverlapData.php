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
        /** @var OverlapPosterData[] */
        #[DataCollectionOf(OverlapPosterData::class)]
        public array $posters,
    ) {}
}