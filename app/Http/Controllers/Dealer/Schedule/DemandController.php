<?php

namespace App\Http\Controllers\Dealer\Schedule;

use App\Data\Post\PostScheduleData;
use App\Enums\PostType;
use App\Http\Controllers\Concerns\HandlesPostSchedule;
use App\Http\Controllers\Controller;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use App\Services\Post\PostService;

class DemandController extends Controller
{
    use HandlesPostSchedule;

    public function __construct(
        private PostService $postService,
        private PostScheduleOverlapService $overlapService,
    ) {}

    protected function postType(): PostType
    {
        return PostType::Demand;
    }

    protected function pageNamespace(): string
    {
        return 'dealer/demands';
    }

    protected function indexRouteName(): string
    {
        return 'dealer.demands.index';
    }

    protected function itemsPropKey(): string
    {
        return 'demands';
    }

    protected function itemPropKey(): string
    {
        return 'demand';
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
