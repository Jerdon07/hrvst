<?php

namespace App\Services\Post;

use App\Data\Post\OverlapPosterData;
use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Schedule\Post;
use Carbon\Carbon;

class PostScheduleOverlapService
{
    /**
     * Same-type posters sharing a date + time slot, excluding the given user.
     * Farmer supplies only ever see other farmers; dealer demands only ever
     * see other dealers — never cross-type. This is a scheduling-collision
     * view ("who else is showing up then"), not a market-imbalance signal —
     * that's VegetableAvailabilityService's job.
     *
     * @return OverlapPosterData[]
     */
    public function overlappingFor(
        PostType $type,
        string|Carbon $scheduledDate,
        PostTimeSlot $timeSlot,
        int $excludeUserId,
    ): array {
        $posts = Post::query()
            ->where('type', $type)
            ->whereDate('scheduled_date', $scheduledDate)
            ->where('time_slot', $timeSlot)
            ->where('user_id', '!=', $excludeUserId)
            ->whereHas('postItems', fn ($q) => $q->ongoing())
            ->with(['user', 'postItems' => fn ($q) => $q->ongoing()->with('vegetable')])
            ->get();

        return OverlapPosterData::collect(
            $posts->map(fn (Post $post) => OverlapPosterData::fromModel($post))->values()->all()
        );
    }

    public function forPost(Post $post): array
    {
        return $this->overlappingFor($post->type, $post->scheduled_date, $post->time_slot, $post->user_id);
    }
}