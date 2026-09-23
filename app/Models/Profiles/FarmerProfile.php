<?php

namespace App\Models\Profiles;

use App\Enums\Post\PostType;
use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['user_id', 'province_id', 'municipality_id', 'barangay_id', 'latitude', 'longitude'])]
class FarmerProfile extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /* ---------- relations ---------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id', 'user_id')
            ->where('type', PostType::Supply);
    }

    public function supplyItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            PostItem::class,
            Post::class,
            'user_id',
            'post_id',
            'user_id',
            'id',
        )->where('posts.type', PostType::Supply);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    /* ---------- scopes ---------- */

    public function ongoingSupplies(): Builder
    {
        return $this->supplyItems()->where(function ($q) {
            $q->ongoing();
        });
    }
}
