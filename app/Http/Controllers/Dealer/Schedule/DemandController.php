<?php

namespace App\Http\Controllers\Dealer\Schedule;

use App\Actions\Post\CreatePostAction;
use App\Data\Post\PostScheduleData;
use App\Enums\PostType;
use App\Http\Controllers\Concerns\HandlesPostSchedule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDemandRequest;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use App\Services\Post\PostService;
use Illuminate\Http\RedirectResponse;

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

    /**
     * See SupplyController::store() for why this override is required —
     * HandlesPostSchedule::store() type-hints the abstract StorePostRequest,
     * which the container cannot instantiate on its own.
     */
    public function store(StoreDemandRequest $request, CreatePostAction $action): RedirectResponse
    {
        $action->handle(
            userId: $request->user()->id,
            type: $this->postType(),
            validated: $request->validated(),
        );

        return redirect()->route($this->indexRouteName())
            ->with('flash', ['type' => 'success', 'message' => $this->createdMessage()]);
    }
}