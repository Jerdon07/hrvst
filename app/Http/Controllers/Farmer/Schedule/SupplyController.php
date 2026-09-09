<?php

namespace App\Http\Controllers\Farmer\Schedule;

use App\Actions\Farmer\UpdateSupplyAction;
use App\Actions\Post\CreatePostAction;
use App\Actions\Post\DeletePostAction;
use App\Data\Post\FarmerSupplyData;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Farmer\StoreSupplyRequest;
use App\Http\Requests\Farmer\UpdateSupplyRequest;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use App\Services\Post\PostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplyController extends Controller
{
    public function __construct(
        private PostService $postService,
        private PostScheduleOverlapService $overlapService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Post::class);

        $userId = $request->user()->id;

        return Inertia::render('farmer/supplies/Index', [
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions(
                type: PostType::Supply
            )),
            'needsAction' => Inertia::defer(fn () => FarmerSupplyData::collect(
                $this->postService->needsAction(
                    type: PostType::Supply,
                    userId: $userId,
                )
            )),
            'supplies' => Inertia::defer(fn () => FarmerSupplyData::collect(
                $this->postService->paginated(
                    type: PostType::Supply,
                    userId: $userId,
                    status: PostItemStatus::Ongoing,
                )
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

        return Inertia::render('farmer/supplies/Archived', [
            'filters' => ['status' => $status->value],
            'supplies' => Inertia::defer(fn () => FarmerSupplyData::collect(
                $this->postService->paginated(
                    type: PostType::Supply,
                    userId: $userId,
                    status: $status
                )
            )),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', [Post::class, PostType::Supply]);

        return Inertia::render('farmer/supplies/Create', [
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions(PostType::Supply)),
        ]);
    }

    public function show(Post $supply): Response
    {
        Gate::authorize('view', $supply);

        $supply->load('postItems.vegetable');

        return Inertia::render('farmer/supplies/Show', [
            'supply' => FarmerSupplyData::from($supply),
            'overlap' => Inertia::defer(fn () => $this->overlapService->forPost($supply)),
        ]);
    }

    public function edit(Post $supply): Response
    {
        Gate::authorize('update', $supply);

        $supply->load('postItems.vegetable');

        return Inertia::render('farmer/supplies/Edit', [
            'supply' => FarmerSupplyData::from($supply),
            'varietyOptions' => Inertia::defer(fn () => $this->postService->varietyOptions(PostType::Supply)),
            'overlap' => Inertia::defer(fn () => $this->overlapService->forPost($supply)),
        ]);
    }

    public function store(StoreSupplyRequest $request, CreatePostAction $action): RedirectResponse
    {
        Gate::authorize('create', [Post::class, PostType::Supply]);

        $action->handle(
            userId: $request->user()->id,
            type: PostType::Supply,
            validated: $request->validated()
        );

        return redirect()->route('farmer.supplies.index')
            ->with('flash', ['type' => 'success', 'message' => 'Supply posted successfully!']);
    }

    public function update(UpdateSupplyRequest $request, Post $supply, UpdateSupplyAction $action): RedirectResponse
    {
        Gate::authorize('update', $supply);

        $action->handle(post: $supply, validated: $request->validated());

        return redirect()->route('farmer.supplies.show', $supply)
            ->with('flash', ['type' => 'success', 'message' => 'Supply updated successfully!']);
    }

    public function destroy(Post $supply, DeletePostAction $action): RedirectResponse
    {
        Gate::authorize('delete', $supply);
        $action->handle($supply);

        return back(fallback: route('farmer.supplies.index'))
            ->with('flash', ['type' => 'success', 'message' => 'Supply deleted.']);
    }
}