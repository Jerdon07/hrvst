<?php

namespace App\Http\Controllers\Farmer\Schedule;

use App\Actions\Post\CreatePostAction;
use App\Actions\Post\UpdatePostAction;
use App\Data\Post\PostScheduleData;
use App\Enums\PostType;
use App\Http\Controllers\Concerns\HandlesPostSchedule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreSupplyRequest;
use App\Http\Requests\Farmer\UpdateSupplyRequest;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use App\Services\Post\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SupplyController extends Controller
{
    use HandlesPostSchedule;

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

    public function store(StoreSupplyRequest $request, CreatePostAction $action): RedirectResponse
    {
        $action->handle(
            userId: $request->user()->id,
            type: $this->postType(),
            validated: $request->validated(),
        );

        return redirect()->route($this->indexRouteName())
            ->with('flash', ['type' => 'success', 'message' => $this->createdMessage()]);
    }

    public function update(UpdateSupplyRequest $request, Post $post, UpdatePostAction $action): RedirectResponse
    {
        Gate::authorize('update', $post);

        $action->handle(post: $post, validated: $request->validated());

        return redirect()->route("{$this->routePrefix()}.show", $post)
            ->with('flash', ['type' => 'success', 'message' => $this->updatedMessage()]);
    }
}