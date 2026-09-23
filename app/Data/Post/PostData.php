<?php

namespace App\Data\Post;

use App\Data\PostItem\PostItemData;
use App\Enums\Post\PostType;
use App\Models\Schedule\Post;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PostData extends Data
{
    public function __construct(
        public int $id,
        public PostType $type,
        public ?string $scheduled_date,
        public ?string $time_slot,
        public ?string $time_slot_label,
        public ?int $days_until_transaction,
        public string $created_at,
        public string $created_at_human,
        public string|Lazy $image_url,
        /** @var PostItemData[]|Lazy */
        public array|Lazy $items,
    ) {}

    public static function fromModel(Post $post): self
    {
        return new self(
            id: $post->id,
            type: $post->type,
            scheduled_date: $post->scheduled_date?->format('M d, Y'),
            time_slot: $post->time_slot?->value,
            time_slot_label: $post->time_slot?->label(),
            days_until_transaction: $post->scheduled_date
                ? (int) now()->diffInDays($post->scheduled_date, false)
                : null,
            created_at: $post->created_at->format('M d, Y'),
            created_at_human: $post->created_at->diffForHumans(),
            image_url: Lazy::whenLoaded('media', $post, fn () => $post->getFirstMediaUrl('post_image')),
            items: Lazy::whenLoaded('postItems', $post, fn () => PostItemData::collect(
                $post->postItems
            )),
        );
    }
}
