<?php

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Billing\Subscription;
use App\Models\Profiles\Role;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

function subscribeViewerTo(App\Models\User $user, SubscriptionFeature $feature): void
{
    Subscription::create([
        'user_id' => $user->id,
        'feature' => $feature,
        'plan' => SubscriptionPlan::Monthly,
        'status' => SubscriptionStatus::Active,
        'amount_cents' => 9_900,
        'currency' => 'PHP',
        'payment_gateway' => 'mock',
        'payment_reference' => 'mock_'.uniqid(),
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACCESS CONTROL — universal: farmer, dealer, and admin all reach this route
// ═══════════════════════════════════════════════════════════════════════════════

describe('unified user profile access control', function () {
    it('redirects a guest to login', function () {
        $farmer = createFarmerUser();

        get(route('users.show', $farmer))
            ->assertRedirect(route('login'));
    });

    it('lets a farmer view another farmer\'s profile', function () {
        $target = createFarmerUser();

        actingAs(createFarmerUser())
            ->get(route('users.show', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('shared/users/Show')
                ->where('profileType', 'farmer')
            );
    });

    it('lets a dealer view a farmer\'s profile (cross-role)', function () {
        $target = createFarmerUser();

        actingAs(createDealerUser())
            ->get(route('users.show', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('profileType', 'farmer'));
    });

    it('lets a farmer view a dealer\'s profile (cross-role)', function () {
        $target = createDealerUser();

        actingAs(createFarmerUser())
            ->get(route('users.show', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('profileType', 'dealer'));
    });

    it('lets an admin view a farmer\'s profile — the consolidated route replaces admin.farmers.show', function () {
        $target = createFarmerUser();

        actingAs(createAdminUser())
            ->get(route('users.show', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('profileType', 'farmer'));
    });

    it('lets an admin view a dealer\'s profile — the consolidated route replaces admin.dealers.show', function () {
        $target = createDealerUser();

        actingAs(createAdminUser())
            ->get(route('users.show', $target))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('profileType', 'dealer'));
    });

    it('lets a farmer view an admin\'s (basic) profile', function () {
        $admin = createAdminUser();

        actingAs(createFarmerUser())
            ->get(route('users.show', $admin))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('profileType', 'basic'));
    });

    it('returns 404 for a nonexistent user', function () {
        actingAs(createFarmerUser())
            ->get(route('users.show', 999999))
            ->assertNotFound();
    });

    it('a user can view their own profile', function () {
        $farmer = createFarmerUser();

        actingAs($farmer)
            ->get(route('users.show', $farmer))
            ->assertOk();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// PER-VIEWER ANALYTICS GATING
//
// analytics_locked on the resolved `profile` prop must reflect the VIEWER's
// own subscription, never the profile owner's.
// ═══════════════════════════════════════════════════════════════════════════════

describe('analytics gating on the unified profile is per-viewer', function () {
    it('locks analytics for a farmer viewer with no subscription', function () {
        $viewer = createFarmerUser();
        $target = createFarmerUser();

        actingAs($viewer);
        $profile = getDeferredProp(route('users.show', $target), 'shared/users/Show', 'profile');

        expect($profile['analytics_locked'])->toBeTrue();
    });

    it('unlocks analytics for a farmer viewer with an active FarmerForecasts subscription', function () {
        $viewer = createFarmerUser();
        subscribeViewerTo($viewer, SubscriptionFeature::FarmerForecasts);
        $target = createFarmerUser(); // target has no subscription of their own

        actingAs($viewer);
        $profile = getDeferredProp(route('users.show', $target), 'shared/users/Show', 'profile');

        expect($profile['analytics_locked'])->toBeFalse();
    });

    it('does not unlock analytics just because the profile owner (not the viewer) is subscribed', function () {
        // The exact regression this guards against: deriving access from the
        // viewed user instead of $request->user().
        $viewer = createFarmerUser(); // no subscription
        $target = createFarmerUser();
        subscribeViewerTo($target, SubscriptionFeature::FarmerForecasts); // target IS subscribed

        actingAs($viewer);
        $profile = getDeferredProp(route('users.show', $target), 'shared/users/Show', 'profile');

        expect($profile['analytics_locked'])->toBeTrue();
    });

    it('unlocks analytics for a dealer viewer with an active DealerMarketIntel subscription', function () {
        $viewer = createDealerUser();
        subscribeViewerTo($viewer, SubscriptionFeature::DealerMarketIntel);
        $target = createDealerUser();

        actingAs($viewer);
        $profile = getDeferredProp(route('users.show', $target), 'shared/users/Show', 'profile');

        expect($profile['analytics_locked'])->toBeFalse();
    });

    it('does not grant access via the wrong tier subscription', function () {
        $viewer = createDealerUser();
        subscribeViewerTo($viewer, SubscriptionFeature::FarmerForecasts); // wrong tier for a dealer
        $target = createDealerUser();

        actingAs($viewer);
        $profile = getDeferredProp(route('users.show', $target), 'shared/users/Show', 'profile');

        expect($profile['analytics_locked'])->toBeTrue();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// PROFILE TYPE RESOLUTION
//
// Guards the deliberate choice in Shared\UserController::show to branch on
// which profile ROW exists rather than on role name.
// ═══════════════════════════════════════════════════════════════════════════════

describe('profile type resolves from data, not role name alone', function () {
    it('resolves "basic" for an admin with no farmer or dealer profile', function () {
        $admin = createAdminUser();

        actingAs(createFarmerUser());
        $profile = getDeferredProp(route('users.show', $admin), 'shared/users/Show', 'profile');

        expect($profile)->toHaveKey('name')
            ->and($profile)->not->toHaveKey('supply_items');
    });
});