<?php

namespace App\Actions\Admin\User;

use App\Enums\RegistrationRequestStatus;
use App\Models\Address\Municipality;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class ApproveRegistrationRequestAction
{
    public function handle(RegistrationRequest $registrationRequest, ?User $reviewer): User
    {
        abort_if(
            $registrationRequest->status !== RegistrationRequestStatus::Pending,
            409,
            'This request has already been reviewed.'
        );

        $user = DB::transaction(function () use ($registrationRequest, $reviewer) {
            $user = User::create([
                'name' => $registrationRequest->name,
                'phone_number' => $registrationRequest->phone_number,
                'email' => $registrationRequest->email,
                'password' => $registrationRequest->pin,
                'must_change_pin' => false,
            ]);

            $role = Role::where('name', $registrationRequest->role)->firstOrFail();
            $user->roles()->attach($role);

            if ($registrationRequest->role === 'farmer') {
                $this->createFarmerProfile($user, $registrationRequest);
            } else {
                DealerProfile::create(['user_id' => $user->id]);
            }

            $registrationRequest->update([
                'status' => RegistrationRequestStatus::Approved,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);

            return $user;
        });

        return $user;
    }

    private function createFarmerProfile(User $user, RegistrationRequest $registrationRequest): void
    {
        $municipality = Municipality::findOrFail($registrationRequest->municipality_id);

        FarmerProfile::create([
            'user_id' => $user->id,
            'province_id' => $municipality->province_id,
            'municipality_id' => $registrationRequest->municipality_id,
            'barangay_id' => $registrationRequest->barangay_id,
            'latitude' => $registrationRequest->latitude,
            'longitude' => $registrationRequest->longitude,
        ]);
    }
}
