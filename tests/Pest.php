<?php

use App\Enums\Post\PostTimeSlot;
use App\Enums\Post\PostType;
use App\Enums\PostItemStatus;
use App\Models\Address\Barangay;
use App\Models\Address\Municipality;
use App\Models\Address\Province;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Profiles\Role;
use App\Models\Schedule\Post;
use App\Models\Schedule\PostItem;
use App\Models\User;
use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Global Hooks
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Storage::fake('public');
    config(['inertia.ssr.enabled' => false]);
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Shared Helpers
|--------------------------------------------------------------------------
*/

// ─── Address ─────────────────────────────────────────────────────────────────

function createBarangay(
    string $provinceName = 'Benguet',
    string $municipalityName = 'La Trinidad',
    string $barangayName = 'Poblacion',
): Barangay {
    $province = Province::create(['name' => $provinceName]);
    $municipality = Municipality::create([
        'province_id' => $province->id,
        'name' => $municipalityName,
        'latitude' => 16.4617,
        'longitude' => 120.5885,
    ]);

    return Barangay::create([
        'municipality_id' => $municipality->id,
        'name' => $barangayName,
    ]);
}

// ─── Users ───────────────────────────────────────────────────────────────────

function createAdminUser(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin' => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'admin']));

    return $user;
}

function createFarmerUser(?Barangay $barangay = null, array $overrides = []): User
{
    $barangay ??= createBarangay();

    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin' => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'farmer']));

    FarmerProfile::create([
        'user_id' => $user->id,
        'province_id' => $barangay->municipality->province_id,
        'municipality_id' => $barangay->municipality_id,
        'barangay_id' => $barangay->id,
        'latitude' => 16.4137,
        'longitude' => 120.5960,
    ]);

    return $user;
}

function createDealerUser(array $overrides = []): User
{
    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'must_change_pin' => false,
    ], $overrides));

    $user->roles()->attach(Role::firstOrCreate(['name' => 'dealer']));
    DealerProfile::create(['user_id' => $user->id]);

    return $user;
}

// ─── Vegetables ────────────────────────────────────────────────────────────────

function createVegetable(): Vegetable
{
    $category = Category::firstOrcreate(['name' => 'Leafy Greens']);

    return Vegetable::create([
        'category_id' => $category->id,
        'vegetable_name' => 'Vegetable '.uniqid(),
    ]);
}

// ─── Posts ───────────────────────────────────────────────────────────────────

function createSupplyPost(User $farmer, Vegetable $vegetable, array $overrides = []): Post
{
    $post = Post::create(array_merge([
        'user_id' => $farmer->id,
        'type' => PostType::Supply,
        'scheduled_date' => now()->addDays(7)->toDateString(),
        'time_slot' => PostTimeSlot::Morning,
    ], $overrides));

    PostItem::create([
        'post_id' => $post->id,
        'vegetable_id' => $vegetable->id,
        'quantity_kg' => 100,
        'status' => PostItemStatus::Ongoing,
    ]);

    return $post;
}

function createDemandPost(User $dealer, Vegetable $vegetable, array $overrides = []): Post
{
    $itemStatus = $overrides['item_status'] ?? PostItemStatus::Ongoing;
    unset($overrides['item_status']);

    $post = Post::create(array_merge([
        'user_id' => $dealer->id,
        'type' => PostType::Demand,
        'scheduled_date' => now()->addDays(5)->toDateString(),
        'time_slot' => PostTimeSlot::Afternoon,
    ], $overrides));

    PostItem::create([
        'post_id' => $post->id,
        'vegetable_id' => $vegetable->id,
        'quantity_kg' => 50,
        'status' => $itemStatus,
    ]);

    return $post;
}

// ─── Defer ───────────────────────────────────────────────────────────────────

function getDeferredProp(string $url, string $component, string $prop): mixed
{
    $page = test()->get($url);
    $page->assertOk();

    preg_match('/data-page="([^"]+)"/', $page->getContent(), $matches);
    $pageData = json_decode(htmlspecialchars_decode($matches[1]), true);
    $version = $pageData['version'] ?? null;

    $response = test()->get($url, [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Data' => $prop,
        'X-Inertia-Partial-Component' => $component,
    ]);

    $response->assertOk();

    return $response->json("props.{$prop}");
}
