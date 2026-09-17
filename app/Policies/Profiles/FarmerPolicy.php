<?php

namespace App\Policies\Profiles;

use App\Models\Profiles\FarmerProfile;
use App\Models\User;

class FarmerPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, FarmerProfile $farmerProfile): bool
    {
        return true;
    }

    public function update(User $user, FarmerProfile $farmerProfile): bool
    {
        return $user->hasRole('admin')
            || $user->farmerProfile?->id === $farmerProfile->id;
    }

    public function delete(User $user, FarmerProfile $farmerProfile): bool
    {
        return $this->update($user, $farmerProfile);
    }

    public function restore(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, FarmerProfile $farmerProfile): bool
    {
        return $this->update($user, $farmerProfile);
    }
}
