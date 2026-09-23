<?php

namespace App\Actions\Admin\User;

use App\Models\Address\Municipality;
use App\Models\Profiles\FarmerProfile;
use App\Models\User;

final class CreateFarmerAction
{
    public function __construct(private ProvisionUserAction $provision) {}

    /**
     * @return array{user: User, plain_pin: string}
     */
    public function handle(array $validated): array
    {
        return $this->provision->handle($validated, 'farmer', function (User $user) use ($validated): void {
            $municipality = Municipality::findOrFail($validated['municipality_id']);

            FarmerProfile::create([
                'user_id' => $user->id,
                'province_id' => $municipality->province_id,
                'municipality_id' => $validated['municipality_id'],
                'barangay_id' => $validated['barangay_id'],
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
            ]);
        });
    }
}
