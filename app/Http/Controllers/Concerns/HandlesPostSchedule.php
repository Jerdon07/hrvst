<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\Post\CreatePostAction;
use App\Actions\Post\DeletePostAction;
use App\Actions\Post\UpdatePostAction;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Models\Schedule\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Shared CRUD flow for Post schedules. A "supply" and a "demand" are both
 * just a Post::type — this trait is the single source of truth for the
 * index/archived/create/show/edit/store/update/destroy shape. Concrete
 * controllers (Farmer\Schedule\SupplyController, Dealer\Schedule\DemandController)
 * only declare the 3 things that actually differ: the PostType, the Inertia
 * page folder, and the index route name for redirects/flash targets.
 *
 * Requires the concrete controller to inject PostService, PostScheduleOverlapService,
 * CreatePostAction, UpdatePostAction, DeletePostAction via constructor —
 * see HandlesPostItemLifecycle for the equivalent pattern already in use
 * for fulfill/expire.
 */
trait HandlesPostSchedule
{
    abstract protected function postType(): PostType;

    /** e.g. 'farmer/supplies' or 'dealer/demands' — matches the Inertia page folder. */
    abstract protected function pageNamespace(): string;

    /** e.g. 'farmer.supplies.index' or 'dealer.demands.index' */
    abstract protected function indexRouteName(): string;

    /** Prop key the Index/Archived/Show pages expect ('supplies' or 'demands'). */
    abstract protected function itemsPropKey(): string;

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;

        return Inertia::render("{$this->pageNamespace()}/Index", [
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions($this->postType())),
            'needsAction' => Inertia::defer(fn () => $this->collectData(
                $this->postService->needsAction($this->postType(), $userId)
            )),
            $this->itemsPropKey() => Inertia::defer(fn () => $this->collectData(
                $this->postService->paginated($this->postType(), $userId, PostItemStatus::Ongoing)
            )),
        ]);
    }

    public function archived(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;
        $status = PostItemStatus::tryFrom($request->query('status', PostItemStatus::Expired->value));

        if (! $status || $status === PostItemStatus::Ongoing) {
            $status = PostItemStatus::Expired;
        }

        return Inertia::render("{$this->pageNamespace()}/Archived", [
            'filters' => ['status' => $status->value],
            $this->itemsPropKey() => Inertia::defer(fn () => $this->collectData(
                $this->postService->paginated($this->postType(), $userId, $status)
            )),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', [Post::class, $this->postType()]);

        return Inertia::render("{$this->pageNamespace()}/Create", [
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions($this->postType())),
        ]);
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        $post->load('postItems.vegetable');

        return Inertia::render("{$this->pageNamespace()}/Show", [
            $this->itemPropKey() => $this->fromModel($post),
            'overlap' => Inertia::defer(fn () => $this->overlapService->forPost($post)),
        ]);
    }

    public function edit(Post $post): Response
    {
        Gate::authorize('update', $post);

        $post->load('postItems.vegetable');

        return Inertia::render("{$this->pageNamespace()}/Edit", [
            $this->itemPropKey() => $this->fromModel($post),
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions($this->postType())),
        ]);
    }

    public function store(StorePostRequest $request, CreatePostAction $action): RedirectResponse
    {
        Gate::authorize('create', [Post::class, $this->postType()]);

        $action->handle(
            userId: $request->user()->id,
            type: $this->postType(),
            validated: $request->validated(),
        );

        return redirect()->route($this->indexRouteName())
            ->with('flash', ['type' => 'success', 'message' => $this->createdMessage()]);
    }

    public function update(UpdatePostRequest $request, Post $post, UpdatePostAction $action): RedirectResponse
    {
        Gate::authorize('update', $post);

        $action->handle(post: $post, validated: $request->validated());

        return redirect()->route("{$this->routePrefix()}.show", $post)
            ->with('flash', ['type' => 'success', 'message' => $this->updatedMessage()]);
    }

    public function destroy(Post $post, DeletePostAction $action): RedirectResponse
    {
        Gate::authorize('delete', $post);
        $action->handle($post);

        return back(fallback: route($this->indexRouteName()))
            ->with('flash', ['type' => 'success', 'message' => $this->deletedMessage()]);
    }

    /**
     * Route prefix used for the singular "show after update" redirect, e.g.
     * 'farmer.supplies' or 'dealer.demands'. Defaults to stripping
     * '.index' off indexRouteName() — override only if that doesn't hold.
     */
    protected function routePrefix(): string
    {
        return str($this->indexRouteName())->beforeLast('.index')->toString();
    }

    /** Prop key the Show/Edit pages expect ('supply' or 'demand', singular). */
    abstract protected function itemPropKey(): string;

    protected function createdMessage(): string
    {
        return $this->postType() === PostType::Supply
            ? 'Supply posted successfully!'
            : 'Request posted successfully!';
    }

    protected function updatedMessage(): string
    {
        return $this->postType() === PostType::Supply
            ? 'Supply updated successfully!'
            : 'Demand updated successfully!';
    }

    protected function deletedMessage(): string
    {
        return $this->postType() === PostType::Supply
            ? 'Supply deleted.'
            : 'Demand deleted.';
    }

    /** @return mixed Data-collection call for the concrete Data class (FarmerSupplyData::collect / DealerDemandData::collect until merged). */
    abstract protected function collectData($items);

    /** @return mixed Data::from() call for the concrete Data class. */
    abstract protected function fromModel(Post $post);
}
