<?php

namespace App\Policies\Profiles;

use App\Models\Profiles\DealerProfile;
use App\Models\User;

class DealerPolicy
{
    public function viewAny(): bool
    {
        return true;
    }

    public function view(User $user, DealerProfile $dealerProfile): bool
    {
        return true;
    }

    public function update(User $user, DealerProfile $dealerProfile): bool
    {
        return $user->hasRole('admin')
            || $user->dealerProfile?->id === $dealerProfile->id;
    }

    public function delete(User $user, DealerProfile $dealerProfile): bool
    {
        return $this->update($user, $dealerProfile);
    }

    public function restore(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, DealerProfile $dealerProfile): bool
    {
        return $this->update($user, $dealerProfile);
    }
}
