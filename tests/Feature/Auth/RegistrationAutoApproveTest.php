<?php

use App\Models\Profiles\Role;
use App\Models\RegistrationRequest;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'dealer']);
    $this->withoutMiddleware();
});

function autoApproveRegistrationPayload(): array
{
    return [
        'role' => 'dealer',
        'name' => 'Auto Approve Test',
        'phone_number' => '09'.fake()->numerify('#########'),
        'email' => null,
        'pin' => '123456',
        'pin_confirmation' => '123456',
        'municipality_id' => null,
        'barangay_id' => null,
        'latitude' => null,
        'longitude' => null,
    ];
}

it('auto-approves and logs in when the flag is on in an allowed environment', function () {
    config(['app.auto_approve_registrations' => true]);
    app()->detectEnvironment(fn () => 'local');

    $this->post('/register', autoApproveRegistrationPayload())
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

it('does NOT auto-approve in production even when the flag is on', function () {
    config(['app.auto_approve_registrations' => true]);
    app()->detectEnvironment(fn () => 'production');

    $this->post('/register', autoApproveRegistrationPayload())
        ->assertRedirect(route('home'));

    $this->assertGuest();

    expect(RegistrationRequest::first()->status->value)->toBe('pending');
});

it('does not auto-approve when the flag is off, regardless of environment', function () {
    config(['app.auto_approve_registrations' => false]);
    app()->detectEnvironment(fn () => 'local');

    $this->post('/register', autoApproveRegistrationPayload())
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

it('leaves the registration request pending when the bypass is blocked by environment', function () {
    config(['app.auto_approve_registrations' => true]);
    app()->detectEnvironment(fn () => 'production');

    $this->post('/register', autoApproveRegistrationPayload());

    expect(RegistrationRequest::first()->status->value)->toBe('pending');
});