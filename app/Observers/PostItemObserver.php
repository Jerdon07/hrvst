<?php

namespace App\Observers;

use App\Enums\Post\PostType;
use App\Enums\PostItemStatus;
use App\Models\Schedule\PostItem;
use Illuminate\Support\Facades\DB;

class PostItemObserver
{
    public function updated(PostItem $postItem): void
    {
        if (! $postItem->wasChanged('status')) {
            return;
        }

        $postItem->loadMissing('post');

        $vegetableId = $postItem->vegetable_id;
        $periodDate = ($postItem->post->scheduled_date ?? $postItem->post->created_at)->startOfMonth()->toDateString();

        $newColumn = $this->resolveColumn($postItem->post->type, $postItem->status);

        if ($newColumn !== null) {
            $this->upsertRow($vegetableId, $periodDate);

            DB::table('vegetable_monthly_stats')
                ->where('vegetable_id', $vegetableId)
                ->where('period_date', $periodDate)
                ->increment($newColumn, (float) $postItem->quantity_kg);
        }

        $rawOldStatus = $postItem->getOriginal('status');
        $oldStatus = $rawOldStatus instanceof PostItemStatus
            ? $rawOldStatus
            : PostItemStatus::tryFrom($rawOldStatus);

        if ($oldStatus !== null) {
            $oldColumn = $this->resolveColumn($postItem->post->type, $oldStatus);

            if ($oldColumn !== null) {
                $qty = (float) $postItem->quantity_kg;

                DB::table('vegetable_monthly_stats')
                    ->where('vegetable_id', $vegetableId)
                    ->where('period_date', $periodDate)
                    ->update([
                        $oldColumn => DB::raw("GREATEST({$oldColumn} - {$qty}, 0)"),
                    ]);
            }
        }
    }

    public function deleted(PostItem $postItem): void
    {
        $postItem->loadMissing('post');

        $column = $this->resolveColumn($postItem->post->type, $postItem->status);

        if ($column === null) {
            return;
        }

        $qty = (float) $postItem->quantity_kg;
        $vegetableId = $postItem->vegetable_id;
        $periodDate = ($postItem->post->scheduled_date ?? $postItem->post->created_at)->startOfMonth()->toDateString();

        DB::table('vegetable_monthly_stats')
            ->where('vegetable_id', $vegetableId)
            ->where('period_date', $periodDate)
            ->update([
                $column => DB::raw("GREATEST({$column} - {$qty}, 0)"),
            ]);
    }

    private function resolveColumn(PostType $type, PostItemStatus $status): ?string
    {
        return match (true) {
            $type === PostType::Supply && $status === PostItemStatus::Expired => 'supply_expired_kg',
            $type === PostType::Supply && $status === PostItemStatus::Fulfilled => 'supply_fulfilled_kg',
            $type === PostType::Demand && $status === PostItemStatus::Expired => 'demand_expired_kg',
            $type === PostType::Demand && $status === PostItemStatus::Fulfilled => 'demand_fulfilled_kg',
            default => null,
        };
    }

    private function upsertRow(int $vegetableId, string $periodDate): void
    {
        DB::table('vegetable_monthly_stats')->upsert(
            [[
                'vegetable_id' => $vegetableId,
                'period_date' => $periodDate,
                'supply_expired_kg' => 0,
                'supply_fulfilled_kg' => 0,
                'demand_expired_kg' => 0,
                'demand_fulfilled_kg' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['vegetable_id', 'period_date'],
            ['updated_at'],
        );
    }
}
