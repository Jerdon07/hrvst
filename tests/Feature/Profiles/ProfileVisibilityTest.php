<?php

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\Profiles\Role;
use Inertia\Inertia;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

function subscribeViewer(App\Models\User $user, SubscriptionFeature $feature): void
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
// ACCESS CONTROL
// ═══════════════════════════════════════════════════════════════════════════════

describe('shared farmer profile access control', function () {
    it('redirects a guest to login', function () {
        $farmer = createFarmerUser();

        get(route('farmers.show', $farmer->farmerProfile))
            ->assertRedirect(route('login'));
    });

    it('allows a farmer to view another farmer\'s profile', function () {
        $viewer = createFarmerUser();
        $target = createFarmerUser();

        actingAs($viewer)
            ->get(route('farmers.show', $target->farmerProfile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('shared/farmers/Show'));
    });

    it('allows a dealer to view a farmer\'s profile (cross-role)', function () {
        $dealer = createDealerUser();
        $farmer = createFarmerUser();

        actingAs($dealer)
            ->get(route('farmers.show', $farmer->farmerProfile))
            ->assertOk();
    });

    it('blocks admin from the shared route — admin uses the dedicated admin.farmers.show route instead', function () {
        // This route sits behind the `not-admin` gate. An admin hitting it
        // directly gets 403, not silently redirected — if that's ever
        // undesired (e.g. an admin clicking a link generated for a
        // farmer/dealer viewer), fix it at the link-generation site, not by
        // loosening this gate.
        $admin = createAdminUser();
        $farmer = createFarmerUser();

        actingAs($admin)
            ->get(route('farmers.show', $farmer->farmerProfile))
            ->assertForbidden();
    });

    it('returns 404 for a nonexistent farmer profile', function () {
        actingAs(createFarmerUser())
            ->get(route('farmers.show', 999999))
            ->assertNotFound();
    });
});

describe('shared dealer profile access control', function () {
    it('redirects a guest to login', function () {
        $dealer = createDealerUser();

        get(route('dealers.show', $dealer->dealerProfile))
            ->assertRedirect(route('login'));
    });

    it('allows a dealer to view another dealer\'s profile', function () {
        $viewer = createDealerUser();
        $target = createDealerUser();

        actingAs($viewer)
            ->get(route('dealers.show', $target->dealerProfile))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('shared/dealers/Show'));
    });

    it('allows a farmer to view a dealer\'s profile (cross-role)', function () {
        $farmer = createFarmerUser();
        $dealer = createDealerUser();

        actingAs($farmer)
            ->get(route('dealers.show', $dealer->dealerProfile))
            ->assertOk();
    });

    it('blocks admin from the shared route', function () {
        $admin = createAdminUser();
        $dealer = createDealerUser();

        actingAs($admin)
            ->get(route('dealers.show', $dealer->dealerProfile))
            ->assertForbidden();
    });

    it('returns 404 for a nonexistent dealer profile', function () {
        actingAs(createDealerUser())
            ->get(route('dealers.show', 999999))
            ->assertNotFound();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// PER-VIEWER ANALYTICS GATING
//
// analytics_locked must reflect the VIEWER's own subscription, never the
// profile owner's — this is the whole point of the change from the
// hardcoded `false` in the first draft.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Inertia::defer() props are withheld from the initial page response and
 * fetched by a follow-up partial reload the client triggers automatically.
 * To assert on a deferred prop's resolved value from a feature test, that
 * partial reload has to be simulated explicitly via the X-Inertia-* headers
 * — hitting the plain route and reading assertInertia() will only ever see
 * the prop as absent, which would let a broken gating rule pass silently.
 */
function getDeferredProp(string $url, string $component, string $prop): mixed
{
    // With the X-Inertia header present, Laravel Inertia returns a raw
    // JsonResponse for the page object — not a Blade view — so this reads
    // via ->json(), not viewData('page'), which only applies to a full
    // (non-partial) initial page load.
    $response = test()->get($url, [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => Inertia::getVersion(),
        'X-Inertia-Partial-Data' => $prop,
        'X-Inertia-Partial-Component' => $component,
    ]);

    $response->assertOk();

    return $response->json("props.{$prop}");
}

describe('analytics gating is per-viewer, not per-profile-owner', function () {
    it('locks analytics for a farmer viewer with no subscription', function () {
        $viewer = createFarmerUser();
        $target = createFarmerUser();

        actingAs($viewer);
        $farmerData = getDeferredProp(
            route('farmers.show', $target->farmerProfile),
            'shared/farmers/Show',
            'farmer',
        );

        expect($farmerData['analytics_locked'])->toBeTrue();
    });

    it('unlocks analytics for a farmer viewer with an active FarmerForecasts subscription, viewing an unsubscribed target', function () {
        $viewer = createFarmerUser();
        subscribeViewer($viewer, SubscriptionFeature::FarmerForecasts);
        $target = createFarmerUser(); // target has NO subscription of their own

        actingAs($viewer);
        $farmerData = getDeferredProp(
            route('farmers.show', $target->farmerProfile),
            'shared/farmers/Show',
            'farmer',
        );

        expect($farmerData['analytics_locked'])->toBeFalse();
    });

    it('does not unlock analytics just because the profile owner (not the viewer) is subscribed', function () {
        // The exact regression this suite exists to prevent: the first draft
        // hardcoded false, and a naive fix might derive access from the
        // profile being viewed rather than $request->user(). Both bugs would
        // pass every other test in this file — this is the one that catches
        // them.
        $viewer = createFarmerUser(); // viewer has NO subscription
        $target = createFarmerUser();
        subscribeViewer($target, SubscriptionFeature::FarmerForecasts); // target IS subscribed

        actingAs($viewer);
        $farmerData = getDeferredProp(
            route('farmers.show', $target->farmerProfile),
            'shared/farmers/Show',
            'farmer',
        );

        expect($farmerData['analytics_locked'])->toBeTrue();
    });

    it('unlocks analytics for a dealer viewer with an active DealerMarketIntel subscription', function () {
        $viewer = createDealerUser();
        subscribeViewer($viewer, SubscriptionFeature::DealerMarketIntel);
        $target = createDealerUser();

        actingAs($viewer);
        $dealerData = getDeferredProp(
            route('dealers.show', $target->dealerProfile),
            'shared/dealers/Show',
            'dealer',
        );

        expect($dealerData['analytics_locked'])->toBeFalse();
    });

    it('does not grant a dealer viewer access via a farmer-tier subscription', function () {
        $viewer = createDealerUser();
        subscribeViewer($viewer, SubscriptionFeature::FarmerForecasts); // wrong tier for this role
        $target = createDealerUser();

        actingAs($viewer);
        $dealerData = getDeferredProp(
            route('dealers.show', $target->dealerProfile),
            'shared/dealers/Show',
            'dealer',
        );

        expect($dealerData['analytics_locked'])->toBeTrue();
    });

    it('locks analytics for a cross-role viewer (farmer viewing a dealer) with no subscription', function () {
        $farmer = createFarmerUser();
        $dealer = createDealerUser();

        actingAs($farmer);
        $dealerData = getDeferredProp(
            route('dealers.show', $dealer->dealerProfile),
            'shared/dealers/Show',
            'dealer',
        );

        expect($dealerData['analytics_locked'])->toBeTrue();
    });
});