<?php

namespace App\Actions\Post;

use App\Models\Schedule\Post;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class UpdatePostAction
{
    public function __construct(private NotifyPostScheduleOverlapAction $notifyOverlap) {}

    public function handle(Post $post, array $validated): Post
    {
        $updated = DB::transaction(function () use ($post, $validated) {
            $post->update(Arr::only($validated, ['scheduled_date', 'time_slot']));

            if (array_key_exists('items', $validated)) {
                $this->syncItems($post, $validated['items']);
            }

            return $post->fresh(['postItems.vegetable']);
        });

        $this->notifyOverlap->handle($updated);

        return $updated;
    }

    private function syncItems(Post $post, array $items): void
    {
        $incoming = collect($items);
        $submittedIds = $incoming->pluck('id')->filter()->values()->all();

        $post->postItems()->when(
            empty($submittedIds),
            fn ($q) => $q,
            fn ($q) => $q->whereNotIn('id', $submittedIds),
        )->delete();

        foreach ($incoming as $item) {
            $data = Arr::only($item, ['vegetable_id', 'quantity_kg']);

            if (! empty($item['id'])) {
                $post->postItems()->where('id', $item['id'])->update($data);
            } else {
                $post->postItems()->create($data);
            }
        }
    }
}