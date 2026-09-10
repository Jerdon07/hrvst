<?php

namespace App\Http\Controllers\Api;

use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Http\Controllers\Controller;
use App\Models\Schedule\Post;
use App\Services\Post\PostScheduleOverlapService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PostOverlapController extends Controller
{
    public function __construct(private PostScheduleOverlapService $overlapService) {}

    /**
     * Live overlap preview for the supply/demand create & edit forms.
     * Generic over Post::type — replaces the previously duplicated
     * (and, on the dealer side, incomplete) per-role overlap logic
     * in Farmer\Schedule\SupplyController and Dealer\Schedule\DemandController.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(PostType::class)],
            'scheduled_date' => ['nullable', 'date_format:Y-m-d'],
            'time_slot' => ['nullable', Rule::enum(PostTimeSlot::class)],
            'vegetable_ids' => ['nullable', 'array'],
            'vegetable_ids.*' => ['integer', 'exists:vegetables,id'],
            'post_id' => [
                'nullable', 'integer',
                Rule::exists('posts', 'id')->where('type', $request->input('type')),
            ],
        ]);

        if (empty($validated['scheduled_date']) || empty($validated['time_slot'])) {
            return response()->json([]);
        }

        $type = PostType::from($validated['type']);

        $post = isset($validated['post_id'])
            ? Post::findOrFail($validated['post_id'])
            : new Post(['user_id' => $request->user()->id, 'type' => $type]);

        Gate::authorize($post->exists ? 'update' : 'create', $post->exists ? $post : [Post::class, $type]);

        $overlap = $this->overlapService->forPostAt(
            post: $post,
            scheduledDate: Carbon::parse($validated['scheduled_date']),
            timeSlot: PostTimeSlot::from($validated['time_slot']),
            vegetableIds: collect($validated['vegetable_ids'] ?? []),
        );

        return response()->json($overlap);
    }
}