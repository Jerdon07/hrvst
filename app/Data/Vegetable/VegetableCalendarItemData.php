<?php

namespace App\Data\Vegetable;

use App\Enums\Post\PostType;
use App\Enums\PostItemStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableCalendarItemData extends Data
{
    public function __construct(
        public int $post_id,
        public PostType $type,
        public ?string $variety_name,
        public float $quantity_kg,
        public PostItemStatus $status,
        public string $poster_name,
        public string $poster_phone,
    ) {}

    public static function fromQueryRow(object $row): self
    {
        return new self(
            post_id: (int) $row->post_id,
            type: $row->type instanceof PostType ? $row->type : PostType::from($row->type),
            variety_name: $row->variety_name,
            quantity_kg: (float) $row->quantity_kg,
            status: $row->status instanceof PostItemStatus ? $row->status : PostItemStatus::from($row->status),
            poster_name: $row->poster_name,
            poster_phone: $row->poster_phone,
        );
    }
}
