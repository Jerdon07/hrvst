<?php

namespace App\Http\Controllers\Farmer\Schedule;

use App\Data\Post\PostScheduleData;
use App\Enums\PostType;
use App\Http\Controllers\Concerns\HandlesPostSchedule;
use App\Http\Controllers\Controller;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use App\Services\Post\PostService;

class SupplyController extends Controller
{
    use HandlesPostSchedule;

    public function __construct(
        private PostService $postService,
        private PostScheduleOverlapService $overlapService,
    ) {}

    protected function postType(): PostType
    {
        return PostType::Supply;
    }

    protected function pageNamespace(): string
    {
        return 'farmer/supplies';
    }

    protected function indexRouteName(): string
    {
        return 'farmer.supplies.index';
    }

    protected function itemsPropKey(): string
    {
        return 'supplies';
    }

    protected function itemPropKey(): string
    {
        return 'supply';
    }

    protected function collectData($items)
    {
        return PostScheduleData::collect($items);
    }

    protected function fromModel(Post $post)
    {
        return PostScheduleData::from($post);
    }
}
