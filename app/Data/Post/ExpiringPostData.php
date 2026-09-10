<?php

namespace App\Data\Post;

use App\Data\PostItem\PostItemLightData;
use App\Enums\PostTimeSlot;
use App\Models\Schedule\Post;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ExpiringPostData extends Data
{
    public function __construct(
        public int $id,
        public string $scheduled_date,
        public PostTimeSlot $time_slot,
        public string $time_slot_label,
        public string $created_at,
        public string $created_at_human,
        public string|Lazy $image_url,
        /** @var PostItemLightData[]|Lazy */
        public array|Lazy $items,
    ) {}

    public static function fromModel(Post $post): self
    {
        return new self(
            id: $post->id,
            scheduled_date: $post->scheduled_date?->format('M d, Y'),
            time_slot: $post->time_slot,
            time_slot_label: $post->time_slot?->label(),
            created_at: $post->created_at->format('M d, Y'),
            created_at_human: $post->created_at->diffForHumans(),
            image_url: Lazy::whenLoaded('media', $post, fn () => $post->getFirstMediaUrl('post_image')),
            items: Lazy::whenLoaded('postItems', $post, fn () => PostItemLightData::collect(
                $post->postItems
            )),
        );
    }
}
