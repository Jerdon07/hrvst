<?php

namespace App\Data\Post;

use App\Models\Schedule\PostItem;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class OverlapPosterData extends Data
{
    public function __construct(
        public int $poster_id,
        public string $poster_name,
        public string $poster_phone,
        public float $quantity_kg,
    ) {}

    public static function fromPostItem(PostItem $item): self
    {
        return new self(
            poster_id: $item->post->user->hasRole('farmer') ? $item->post->user->farmerProfile->id : ($item->post->user->dealerProfile->id ?? null),
            poster_name: $item->post->user->name,
            poster_phone: $item->post->user->phone_number,
            quantity_kg: (float) $item->quantity_kg,
        );
    }
}
