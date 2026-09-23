<?php

namespace App\Models\Schedule;

use App\Enums\Post\PostTimeSlot;
use App\Enums\Post\PostType;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable([
    'user_id',
    'type',
    'scheduled_date',
    'time_slot',
    'due_today_notified_at',
    'reminder_morning_notified_at',
    'reminder_evening_notified_at',
])]
class Post extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    public const int ACTION_WINDOW_DAYS = 5;

    protected function casts(): array
    {
        return [
            'type' => PostType::class,
            'time_slot' => PostTimeSlot::class,
            'scheduled_date' => 'date:F j, Y',
        ];
    }

    /* ---------- relationships ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function postItems(): HasMany
    {
        return $this->hasMany(PostItem::class)->chaperone();
    }

    public function farmerProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            FarmerProfile::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id',
        );
    }

    public function dealerProfile(): HasOneThrough
    {
        return $this->hasOneThrough(
            DealerProfile::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id',
        );
    }

    /* ---------- scopes ---------- */

    public function scopeSupply(Builder $query): Builder
    {
        return $query->where('type', PostType::Supply);
    }

    public function scopeDemand(Builder $query): Builder
    {
        return $query->where('type', PostType::Demand);
    }

    /* ---------- action window ---------- */

    public function isWithinActionWindow(): bool
    {
        if ($this->scheduled_date === null) {
            return false;
        }

        return today()->between(
            $this->scheduled_date,
            $this->scheduled_date->addDays(self::ACTION_WINDOW_DAYS - 1),
        );
    }

    public function isPastActionWindow(): bool
    {
        if ($this->scheduled_date === null) {
            return false;
        }

        return today()->gte($this->scheduled_date->addDays(self::ACTION_WINDOW_DAYS));
    }

    public function needsAction(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->scheduled_date !== null && ! $this->scheduled_date->isFuture()
        );
    }

    /* ---------- lifecycle ---------- */

    public function markAsExpired(): void
    {
        $this->postItems()->each(fn (PostItem $item) => $item->markAsExpired());
    }

    /* ---------- helpers ---------- */

    public function createdAtHuman(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }

    protected static function booted(): void
    {
        static::deleting(function (Post $post): void {
            if (! $post->isForceDeleting()) {
                return;
            }

            $post->postItems()->each(fn (PostItem $item) => $item->delete());
        });
    }
}
