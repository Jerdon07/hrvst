<?php

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);
});

// ═══════════════════════════════════════════════════════════════════════════════
// ACCESS CONTROL
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer access control', function () {
    it('redirects guest to login on index', function () {
        get(route('admin.farmers.index'))
            ->assertRedirect(route('login'));
    });

    it('redirects guest to login on markers api', function () {
        get(route('admin.farmers.api.markers'))
            ->assertRedirect(route('login'));
    });

    it('blocks a farmer from all admin farmer routes', function () {
        actingAs(createFarmerUser())
            ->get(route('admin.farmers.index'))
            ->assertForbidden();
    });

    it('blocks a dealer from all admin farmer routes', function () {
        actingAs(createDealerUser())
            ->get(route('admin.farmers.index'))
            ->assertForbidden();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// INDEX
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer index', function () {
    it('renders the farmer list page for admin', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/farmers/Index')
                ->has('filters')
                ->has('mapConfig')
            );
    });

    it('defaults to list view', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('view', 'list')
            );
    });

    it('switches to map view when query param is set', function () {
        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->get(route('admin.farmers.index', ['view' => 'map']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('view', 'map')
                ->where('farmers', null)
            );
    });

    it('passes search filter through to Inertia props', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index', ['search' => 'Jane']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', 'Jane')
            );
    });

    it('passes null search when no search query is given', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.search', null)
            );
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DESTROY
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer destroy', function () {
    it('admin can delete a farmer and is redirected with a success flash', function () {
        $this->withoutExceptionHandling();

        $farmer = createFarmerUser();
        $profile = $farmer->farmerProfile;

        actingAs(createAdminUser())
            ->delete(route('admin.farmers.destroy', $profile))
            ->assertRedirect(route('admin.farmers.index'))
            ->assertSessionHas('flash.type', 'success');
    });

    it('the farmer profile row is hard-deleted', function () {
        $this->withoutExceptionHandling();

        $farmer = createFarmerUser();
        $profile = $farmer->farmerProfile;

        actingAs(createAdminUser())
            ->delete(route('admin.farmers.destroy', $profile));

        expect(FarmerProfile::find($profile->id))->toBeNull();
    });

    it('the associated user account is also deleted', function () {
        $this->withoutExceptionHandling();

        $farmer = createFarmerUser();
        $userId = $farmer->id;

        actingAs(createAdminUser())
            ->delete(route('admin.farmers.destroy', $farmer->farmerProfile));

        expect(User::find($userId))->toBeNull();
    });

    it('farmer cannot delete another farmer via admin route — EnsureUserIsAdmin blocks first', function () {
        $target = createFarmerUser();

        actingAs(createFarmerUser())
            ->delete(route('admin.farmers.destroy', $target->farmerProfile))
            ->assertForbidden();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// MARKERS API
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer markers api', function () {
    it('returns a json response with markers and total keys', function () {
        createFarmerUser();
        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonStructure(['markers', 'total']);
    });

    it('returns empty markers when no farmers exist', function () {
        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonPath('total', 0)
            ->assertJsonPath('markers', []);
    });

    it('total reflects the correct number of farmers', function () {
        createFarmerUser();
        createFarmerUser();

        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonPath('total', 2);
    });

    it('each marker contains the required structure', function () {
        createFarmerUser();

        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk()
            ->assertJsonStructure([
                'markers' => [[
                    'id',
                    'coordinates' => ['lat', 'lng'],
                    'farmer_name',
                    'municipality',
                    'ongoing_supplies_count',
                    'supplies_summary',
                ]],
            ]);
    });

    it('municipality_id filter returns only farmers in that municipality', function () {
        $barangayA = createBarangay('Benguet', 'La Trinidad', 'Pico');   // municipality A
        $barangayB = createBarangay('Benguet', 'Tublay', 'Ambassador');   // municipality B

        createFarmerUser($barangayA);
        createFarmerUser($barangayA); // 2 in A
        createFarmerUser($barangayB); // 1 in B — should be excluded

        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers', [
                'municipality_id' => $barangayA->municipality_id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 2);
    });

    it('vegetable_id filter returns only farmers with an ongoing supply for that vegetable', function () {
        $vegetable = createVegetable();
        $otherVariety = createVegetable();

        $match = createFarmerUser();
        createSupplyPost($match, $vegetable);

        $noMatch = createFarmerUser();
        createSupplyPost($noMatch, $otherVariety);

        createFarmerUser();

        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers', ['vegetable_id' => $vegetable->id]))
            ->assertOk()
            ->assertJsonPath('total', 1);
    });

    it('rejects an invalid municipality_id with a validation error', function () {
        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers', ['municipality_id' => 999999]))
            ->assertUnprocessable();
    });

    it('rejects an invalid vegetable_id with a validation error', function () {
        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers', ['vegetable_id' => 999999]))
            ->assertUnprocessable();
    });
});

// ═══════════════════════════════════════════════════════════════════════════════
// DETAILS API
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer details api', function () {
    it('returns a json response for a valid farmer', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonStructure([
                'id', 'joined_at', 'joined_at_human', 'user',
                'full_address', 'coordinates' => ['lat', 'lng'],
            ]);
    });

    it('user block contains name and email', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonStructure(['user' => ['name', 'email']]);
    });

    it('response includes all address components as flat top-level keys', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonStructure([
                'province', 'municipality', 'barangay', 'full_address',
                'coordinates' => ['lat', 'lng'],
            ]);
    });

    it('supplies list is present and empty when the farmer has no posts', function () {
        $farmer = createFarmerUser();

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonPath('supplies', []);
    });

    it('supplies list includes ongoing supply data', function () {
        $farmer = createFarmerUser();
        $vegetable = createVegetable();
        createSupplyPost($farmer, $vegetable, ['scheduled_date' => today()->toDateString()]);

        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', $farmer->farmerProfile))
            ->assertOk()
            ->assertJsonCount(1, 'supplies');
    });

    it('returns 404 for a nonexistent farmer profile', function () {
        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.details', 999999))
            ->assertNotFound();
    });
});

function subscribeAdminAnalytics(User $admin): void
{
    Subscription::create([
        'user_id' => $admin->id,
        'feature' => SubscriptionFeature::AdminAnalytics,
        'plan' => SubscriptionPlan::Monthly,
        'status' => SubscriptionStatus::Active,
        'amount_cents' => 250_000,
        'currency' => 'PHP',
        'payment_gateway' => 'mock',
        'payment_reference' => 'mock_'.uniqid(),
        'starts_at' => now(),
        'ends_at' => now()->addMonth(),
    ]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// MAP VIEW SUBSCRIPTION GATING
// ═══════════════════════════════════════════════════════════════════════════════

describe('admin farmer map subscription gating', function () {
    it('redirects an unsubscribed admin to billing when requesting the map view', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index', ['view' => 'map']))
            ->assertRedirect(route('billing.show'))
            ->assertSessionHas('flash.type', 'warning');
    });

    it('lets a subscribed admin load the map view', function () {
        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->get(route('admin.farmers.index', ['view' => 'map']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/farmers/Index')
                ->where('view', 'map')
            );
    });

    it('does not gate the list view for an unsubscribed admin', function () {
        actingAs(createAdminUser())
            ->get(route('admin.farmers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('view', 'list'));
    });

    it('blocks the markers API for an unsubscribed admin', function () {
        actingAs(createAdminUser())
            ->getJson(route('admin.farmers.api.markers'))
            ->assertForbidden();
    });

    it('allows the markers API for a subscribed admin', function () {
        $admin = createAdminUser();
        subscribeAdminAnalytics($admin);

        actingAs($admin)
            ->getJson(route('admin.farmers.api.markers'))
            ->assertOk();
    });

    it('does not loop back to billing when an unsubscribed admin returns to the farmer list', function () {
        $admin = createAdminUser();

        $page = $this->actingAs($admin)->visit('/admin/farmers')
            ->click('Map')
            ->assertUrlIs('/billing')
            ->assertNoJavaScriptErrors();

        $page->visit('/admin/farmers')
            ->assertSee('Farmers')
            ->assertUrlIs('/admin/farmers');
    });
});
