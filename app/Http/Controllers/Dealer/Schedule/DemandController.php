<?php

namespace App\Http\Controllers\Dealer\Schedule;

use App\Actions\Dealer\UpdateDemandAction;
use App\Actions\Post\CreatePostAction;
use App\Actions\Post\DeletePostAction;
use App\Data\Post\DealerDemandData;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dealer\StoreDemandRequest;
use App\Http\Requests\Dealer\UpdateDemandRequest;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use App\Services\Post\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DemandController extends Controller
{
    public function __construct(
        private PostService $postService,
        private PostScheduleOverlapService $overlapService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;

        return Inertia::render('dealer/demands/Index', [
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions(
                type: PostType::Demand
            )),
            'needsAction' => Inertia::defer(fn () => DealerDemandData::collect(
                $this->postService->needsAction(
                    type: PostType::Demand,
                    userId: $userId,
                )
            )),
            'demands' => Inertia::defer(fn () => DealerDemandData::collect(
                $this->postService->paginated(
                    PostType::Demand,
                    userId: $userId,
                    status: PostItemStatus::Ongoing)
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

        return Inertia::render('dealer/demands/Archived', [
            'filters' => ['status' => $status->value],
            'demands' => Inertia::defer(fn () => DealerDemandData::collect(
                $this->postService->paginated(
                    type: PostType::Demand,
                    userId: $userId,
                    status: $status
                )
            )),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', [Post::class, PostType::Demand]);

        return Inertia::render('dealer/demands/Create', [
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions(PostType::Demand)),
        ]);
    }

    public function show(Post $demand): Response
    {
        Gate::authorize('view', $demand);

        $demand->load('postItems.vegetable');

        return Inertia::render('dealer/demands/Show', [
            'demand' => DealerDemandData::from($demand),
            'overlap' => Inertia::defer(fn () => $this->overlapService->forPost($demand)),
        ]);
    }

    public function edit(Post $demand): Response
    {
        Gate::authorize('update', $demand);

        $demand->load('postItems.vegetable');

        return Inertia::render('dealer/demands/Edit', [
            'demand' => DealerDemandData::from($demand),
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions(PostType::Demand)),
        ]);
    }

    public function store(StoreDemandRequest $request, CreatePostAction $action): RedirectResponse
    {
        Gate::authorize('create', [Post::class, PostType::Demand]);

        $action->handle(
            userId: $request->user()->id,
            type: PostType::Demand,
            validated: $request->validated(),
        );

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Request posted successfully!']);
    }

    public function update(UpdateDemandRequest $request, Post $demand, UpdateDemandAction $action): RedirectResponse
    {
        Gate::authorize('update', $demand);

        $action->handle(post: $demand, validated: $request->validated());

        return redirect()->route('dealer.demands.show', $demand)
            ->with('flash', ['type' => 'success', 'message' => 'Demand updated successfully!']);
    }

    public function destroy(Post $demand, DeletePostAction $action): RedirectResponse
    {
        Gate::authorize('delete', $demand);
        $action->handle($demand);

        return redirect()->route('dealer.demands.index')
            ->with('flash', ['type' => 'success', 'message' => 'Demand deleted.']);
    }
}