<?php

namespace App\Actions\Admin\User;

use App\Concerns\GeneratesPin;
use App\Models\Profiles\Role;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;

final class ProvisionUserAction
{
    use GeneratesPin;

    /**
     * @param  array{name: string, phone_number: string, email?: ?string}  $validated
     * @param  Closure(User): void  $createProfile  Given the created user, create its role-specific profile row.
     * @return array{user: User, plain_pin: string}
     */
    public function handle(array $validated, string $role, Closure $createProfile): array
    {
        $plainPin = $this->generatePin();

        $user = DB::transaction(function () use ($validated, $plainPin, $role, $createProfile): User {
            $user = User::create([
                'name' => $validated['name'],
                'phone_number' => $validated['phone_number'],
                'email' => $validated['email'] ?? null,
                'password' => $plainPin,
                'must_change_pin' => true,
            ]);

            $user->roles()->attach(Role::where('name', $role)->firstOrFail());

            $createProfile($user);

            return $user;
        });

        return ['user' => $user, 'plain_pin' => $plainPin];
    }
}
