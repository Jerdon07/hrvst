<?php

namespace App\Actions\Admin\User;

use App\Models\Profiles\DealerProfile;
use App\Models\User;

final class CreateDealerAction
{
    public function __construct(private ProvisionUserAction $provision) {}

    /**
     * @return array{user: User, plain_pin: string}
     */
    public function handle(array $validated): array
    {
        return $this->provision->handle($validated, 'dealer', function (User $user): void {
            DealerProfile::create(['user_id' => $user->id]);
        });
    }
}
