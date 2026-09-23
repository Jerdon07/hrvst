<?php

namespace App\Models\Profiles;

use App\Enums\Post\PostType;
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

#[Fillable(['user_id'])]
class DealerProfile extends Model implements HasMedia
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
            ->where('type', PostType::Demand);
    }

    public function demandItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            PostItem::class,
            Post::class,
            'user_id',
            'post_id',
            'user_id',
            'id',
        )->where('posts.type', PostType::Demand);
    }

    /* ---------- scopes ---------- */

    public function ongoingDemands(): Builder
    {
        return $this->demandItems()->where(function ($q) {
            $q->ongoing();
        });
    }
}
