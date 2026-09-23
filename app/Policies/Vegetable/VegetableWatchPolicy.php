<?php

namespace App\Policies\Vegetable;

use App\Models\User;
use App\Models\Vegetable\VegetableWatch;

class VegetableWatchPolicy
{
    public function create(User $user): bool
    {
        return $user->farmerProfile !== null || $user->dealerProfile !== null;
    }

    public function delete(User $user, VegetableWatch $watch): bool
    {
        return $user->id === $watch->user_id;
    }
}
