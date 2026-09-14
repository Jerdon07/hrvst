<?php

namespace App\Http\Controllers\Dealer\Schedule;

use App\Actions\Post\CreatePostAction;
use App\Actions\Post\UpdatePostAction;
use App\Data\Post\PostScheduleData;
use App\Enums\PostType;
use App\Http\Controllers\Concerns\HandlesPostSchedule;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDemandRequest;
use App\Http\Requests\Dealer\UpdateDemandRequest;
use App\Models\Schedule\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DemandController extends Controller
{
    use HandlesPostSchedule;

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

    public function update(UpdateDemandRequest $request, Post $post, UpdatePostAction $action): RedirectResponse
    {
        Gate::authorize('update', $post);

        $action->handle(post: $post, validated: $request->validated());

        return redirect()->route("{$this->routePrefix()}.show", $post)
            ->with('flash', ['type' => 'success', 'message' => $this->updatedMessage()]);
    }
}
