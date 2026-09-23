<?php

namespace App\Models\Vegetable;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'vegetable_id', 'viewer_role', 'last_notified_band', 'last_evaluated_at'])]
class VegetableWatch extends Model
{
    protected function casts(): array
    {
        return [
            'last_evaluated_at' => 'datetime',
        ];
    }

    /* ---------- relations ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vegetable(): BelongsTo
    {
        return $this->belongsTo(Vegetable::class);
    }
}
