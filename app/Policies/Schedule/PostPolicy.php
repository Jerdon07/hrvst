<?php

namespace App\Policies\Schedule;

use App\Enums\PostType;
use App\Models\Schedule\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('dealer') || $user->hasRole('farmer');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function create(User $user, PostType $type): bool
    {
        return match ($type) {
            PostType::Supply => $user->hasRole('farmer') && $user->farmerProfile !== null,
            PostType::Demand => $user->hasRole('dealer') && $user->dealerProfile !== null,
        };
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            && $post->postItems()->ongoing()->exists();
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}