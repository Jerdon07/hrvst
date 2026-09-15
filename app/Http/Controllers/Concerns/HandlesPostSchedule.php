<?php

namespace App\Http\Controllers\Concerns;

use App\Actions\Post\DeletePostAction;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use App\Services\Post\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

trait HandlesPostSchedule
{
    public function __construct(
        private PostService $postService,
        private PostScheduleOverlapService $overlapService,
    ) {}

    abstract protected function postType(): PostType;

    /** e.g. 'farmer.supplies.index' or 'dealer.demands.index' */
    abstract protected function indexRouteName(): string;

    /** @return mixed */
    abstract protected function collectData($items);

    /** @return mixed Data::from() call for the concrete Data class. */
    abstract protected function fromModel(Post $post);

    protected function pageNamespace(): string
    {
        return 'shared/schedule';
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;

        return Inertia::render("{$this->pageNamespace()}/Index", [
            'type' => $this->postType()->value,
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions($this->postType())),
            'needsAction' => Inertia::defer(fn () => $this->collectData(
                $this->postService->needsAction($this->postType(), $userId)
            )),
            'items' => Inertia::defer(fn () => $this->collectData(
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
            'type' => $this->postType()->value,
            'filters' => ['status' => $status->value],
            'items' => Inertia::defer(fn () => $this->collectData(
                $this->postService->paginated($this->postType(), $userId, $status)
            )),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', [Post::class, $this->postType()]);

        return Inertia::render("{$this->pageNamespace()}/Create", [
            'type' => $this->postType()->value,
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions($this->postType())),
        ]);
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        $post->load('postItems.vegetable');

        return Inertia::render("{$this->pageNamespace()}/Show", [
            'type' => $this->postType()->value,
            'schedule' => $this->fromModel($post),
            'overlap' => Inertia::defer(fn () => $this->overlapService->forPost($post)),
        ]);
    }

    public function edit(Post $post): Response
    {
        Gate::authorize('update', $post);

        $post->load('postItems.vegetable');

        return Inertia::render("{$this->pageNamespace()}/Edit", [
            'type' => $this->postType()->value,
            'schedule' => $this->fromModel($post),
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions($this->postType())),
        ]);
    }

    public function destroy(Post $post, DeletePostAction $action): RedirectResponse
    {
        Gate::authorize('delete', $post);
        $action->handle($post);

        return back(fallback: route($this->indexRouteName()))
            ->with('flash', ['type' => 'success', 'message' => $this->deletedMessage()]);
    }

    protected function routePrefix(): string
    {
        return str($this->indexRouteName())->beforeLast('.index')->toString();
    }

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
}
