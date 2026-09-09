<?php

namespace App\Data\Post;

use App\Data\PostItem\PostItemLightData;
use App\Models\Schedule\Post;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OverlapPosterData extends Data
{
    public function __construct(
        public int $post_id,
        public string $poster_name,
        public string $poster_phone,
        public float $total_kg,
        /** @var PostItemLightData[] */
        #[DataCollectionOf(PostItemLightData::class)]
        public array $items,
    ) {}

    public static function fromModel(Post $post): self
    {
        return new self(
            post_id: $post->id,
            poster_name: $post->user->name,
            poster_phone: $post->user->phone_number,
            total_kg: (float) $post->postItems->sum('quantity_kg'),
            items: PostItemLightData::collect($post->postItems),
        );
    }
}