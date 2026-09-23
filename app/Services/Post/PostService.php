<?php

namespace App\Services\Post;

use App\Enums\Post\PostType;
use App\Enums\PostItemStatus;
use App\Models\Schedule\Post;
use App\Services\Vegetable\VegetableOptionsBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    public function __construct(private VegetableOptionsBuilder $optionsBuilder) {}

    public function needsAction(PostType $type, int $userId): Collection
    {
        return Post::query()
            ->where('type', $type)
            ->where('user_id', $userId)
            ->whereHas('postItems', fn ($q) => $q->ongoing())
            ->whereDate('scheduled_date', '<=', today())
            ->with(['media', 'postItems' => fn ($q) => $q->ongoing()->with('vegetable')])
            ->orderBy('scheduled_date')
            ->get();
    }

    public function paginated(
        PostType $type,
        int $userId,
        PostItemStatus $status = PostItemStatus::Ongoing,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Post::query()
            ->where('type', $type)
            ->where('user_id', $userId)
            ->whereHas('postItems', fn ($q) => $q->ofStatus($status))
            ->when(
                $status === PostItemStatus::Ongoing,
                fn ($q) => $q->whereDate('scheduled_date', '>', today()),
            )
            ->with(['media', 'postItems' => fn ($q) => $q->ofStatus($status)->with('vegetable')])
            ->when(
                $status === PostItemStatus::Ongoing,
                fn ($q) => $q->orderBy('scheduled_date'),
                fn ($q) => $q->latest('scheduled_date'),
            )
            ->paginate($perPage);
    }

    public function varietyOptions(PostType $type): array
    {
        return cache()->remember(
            "post_variety_options:{$type->value}",
            3600,
            fn () => $this->optionsBuilder->build(),
        );
    }
}
