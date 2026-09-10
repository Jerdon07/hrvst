<?php

namespace App\Data\Post;

use App\Data\PostItem\PostItemLightData;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class FarmerSupplyData extends Data
{
    public function __construct(
        public int $id,
        public int $user_id,
        public PostType $type,
        #[WithCast(DateTimeInterfaceCast::class, format: 'F j, Y')]
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'F j, Y')]
        #[TypeScriptType('string')]
        public Carbon $scheduled_date,
        public PostTimeSlot $time_slot,
        public string $created_at,
        public string $created_at_human,
        public bool $needs_action,
        /** @var PostItemLightData[] */
        #[DataCollectionOf(PostItemLightData::class)]
        public ?array $post_items,
    ) {}
}
