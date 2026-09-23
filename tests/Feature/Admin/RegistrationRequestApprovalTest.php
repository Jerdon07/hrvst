<?php

use App\Enums\RegistrationRequestStatus;
use App\Models\Address\Barangay;
use App\Models\Profiles\Role;
use App\Models\RegistrationRequest;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

function pendingDealerRequest(array $overrides = []): RegistrationRequest
{
    return RegistrationRequest::create(array_merge([
        'name' => 'Pending Dealer',
        'phone_number' => '09'.fake()->numerify('#########'),
        'email' => null,
        'role' => 'dealer',
        'pin' => '123456',
        'status' => RegistrationRequestStatus::Pending,
    ], $overrides));
}

function pendingFarmerRequest(Barangay $barangay, array $overrides = []): RegistrationRequest
{
    return RegistrationRequest::create(array_merge([
        'name' => 'Pending Farmer',
        'phone_number' => '09'.fake()->numerify('#########'),
        'email' => null,
        'role' => 'farmer',
        'pin' => '123456',
        'municipality_id' => $barangay->municipality_id,
        'barangay_id' => $barangay->id,
        'latitude' => 16.4137,
        'longitude' => 120.5896,
        'status' => RegistrationRequestStatus::Pending,
    ], $overrides));
}

describe('access control', function () {
    it('redirects a guest to login on index', function () {
        get(route('admin.registration-requests.index'))->assertRedirect(route('login'));
    });

    it('blocks a non-admin from approving', function () {
        actingAs(createFarmerUser())
            ->post(route('admin.registration-requests.approve', pendingDealerRequest()))
            ->assertForbidden();
    });

    it('blocks a non-admin from rejecting', function () {
        actingAs(createFarmerUser())
            ->post(route('admin.registration-requests.reject', pendingDealerRequest()))
            ->assertForbidden();
    });
});

describe('approve', function () {
    it('creates a dealer user and marks the request approved', function () {
        $admin = createAdminUser();
        $request = pendingDealerRequest();

        actingAs($admin)
            ->post(route('admin.registration-requests.approve', $request))
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        $request->refresh();

        expect($request->status)->toBe(RegistrationRequestStatus::Approved)
            ->and($request->reviewed_by)->toBe($admin->id)
            ->and($request->reviewed_at)->not->toBeNull();

        $user = User::where('phone_number', $request->phone_number)->firstOrFail();
        expect($user->hasRole('dealer'))->toBeTrue()
            ->and($user->dealerProfile)->not->toBeNull();
    });

    it('creates a farmer user with province_id correctly derived from municipality_id', function () {
        $barangay = createBarangay('Benguet', 'La Trinidad', 'Pico');
        $request = pendingFarmerRequest($barangay);

        actingAs(createAdminUser())->post(route('admin.registration-requests.approve', $request));

        $user = User::where('phone_number', $request->phone_number)->firstOrFail();

        expect($user->hasRole('farmer'))->toBeTrue()
            ->and($user->farmerProfile->province_id)->toBe($barangay->municipality->province_id)
            ->and($user->farmerProfile->municipality_id)->toBe($barangay->municipality_id)
            ->and($user->farmerProfile->barangay_id)->toBe($barangay->id);
    });

    it('does not auto-login the approved user', function () {
        $request = pendingDealerRequest();

        actingAs(createAdminUser())->post(route('admin.registration-requests.approve', $request));

        $newUser = User::where('phone_number', $request->phone_number)->first();

        expect(auth()->id())->not->toBe($newUser->id);
    });

    it('returns 409 when approving an already-reviewed request', function () {
        actingAs(createAdminUser())
            ->post(route('admin.registration-requests.approve', pendingDealerRequest(['status' => RegistrationRequestStatus::Approved])))
            ->assertStatus(409);
    });

    it('does not create a second user when approving the same request twice', function () {
        $request = pendingDealerRequest();
        $admin = createAdminUser();

        actingAs($admin)->post(route('admin.registration-requests.approve', $request));
        actingAs($admin)->post(route('admin.registration-requests.approve', $request))->assertStatus(409);

        expect(User::where('phone_number', $request->phone_number)->count())->toBe(1);
    });
});

describe('reject', function () {
    it('marks the request rejected with a reason and creates no user', function () {
        $admin = createAdminUser();
        $request = pendingDealerRequest();

        actingAs($admin)
            ->post(route('admin.registration-requests.reject', $request), ['reason' => 'Duplicate submission'])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        $request->refresh();

        expect($request->status)->toBe(RegistrationRequestStatus::Rejected)
            ->and($request->rejection_reason)->toBe('Duplicate submission')
            ->and($request->reviewed_by)->toBe($admin->id)
            ->and(User::where('phone_number', $request->phone_number)->exists())->toBeFalse();
    });

    it('allows rejecting without a reason', function () {
        $request = pendingDealerRequest();

        actingAs(createAdminUser())
            ->post(route('admin.registration-requests.reject', $request), [])
            ->assertSessionHasNoErrors();

        expect($request->fresh()->rejection_reason)->toBeNull();
    });

    it('rejects an overlong reason', function () {
        actingAs(createAdminUser())
            ->post(route('admin.registration-requests.reject', pendingDealerRequest()), ['reason' => str_repeat('x', 501)])
            ->assertSessionHasErrors('reason');
    });

    it('returns 409 when rejecting an already-reviewed request', function () {
        actingAs(createAdminUser())
            ->post(route('admin.registration-requests.reject', pendingDealerRequest(['status' => RegistrationRequestStatus::Rejected])))
            ->assertStatus(409);
    });
});
