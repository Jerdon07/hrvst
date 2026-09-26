<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login'), [
        'phone_number' => $user->phone_number,
        'password' => '0000',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt('test-secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->post(route('login'), [
        'phone_number' => $user->phone_number,
        'password' => '0000',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'phone_number' => $user->phone_number,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'phone_number' => $user->phone_number,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post(route('login'), [
        'phone_number' => $user->phone_number,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});

test('login page exposes the system admin phone to guests', function () {
    config(['services.system_admin.phone' => '09171234567']);

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/Login')
            ->where('systemAdminPhone', '09171234567')
        );
});
