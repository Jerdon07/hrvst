<?php

use App\Models\Profiles\Role;
use App\Models\User;
use App\Models\Vegetable\Category;
use App\Models\Vegetable\Vegetable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

// ─── Setup ────────────────────────────────────────────────────────────────────

beforeEach(function () {
    Storage::fake('public');

    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'farmer']);
    Role::firstOrCreate(['name' => 'dealer']);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(Role::where('name', 'admin')->first());

    $this->farmer = User::factory()->create();
    $this->farmer->roles()->attach(Role::where('name', 'farmer')->first());

    $this->category = Category::create(['name' => 'Leafy Greens']);

    $this->vegetable = Vegetable::create([
        'category_id' => $this->category->id,
        'vegetable_name' => 'Pechay',
    ]);
});

// ─── Index ────────────────────────────────────────────────────────────────────

describe('index', function () {
    it('renders the index page for an admin when a category slug is provided', function () {
        actingAs($this->admin)
            ->get(route('admin.vegetables.index', ['category' => $this->category->slug]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('admin/vegetables/Index'));
    });

    it('redirects guests to login', function () {
        get(route('admin.vegetables.index'))
            ->assertRedirect(route('login'));
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->get(route('admin.vegetables.index'))
            ->assertForbidden();
    });
});

describe('category description', function () {
    it('exposes category descriptions to admins', function () {
        $category = Category::create([
            'name' => 'Root Vegetables',
            'description' => 'Vegetables with thick, swollen roots that grow underground.',
        ]);

        actingAs($this->admin)
            ->get(route('admin.vegetables.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/vegetables/Index')
                ->where('categories', fn ($categories) => collect($categories)
                    ->contains(fn ($c) => $c['id'] === $category->id
                        && $c['description'] === $category->description)));
    });
});

// ─── Vegetable Store ──────────────────────────────────────────────────────────

describe('vegetable store', function () {
    it('creates a vegetable without an image', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Kangkong',
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        assertDatabaseHas('vegetables', ['vegetable_name' => 'Kangkong']);
    });

    it('rejects a non-image file', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Kangkong',
                'image' => UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    });

    it('rejects a duplicate name', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Pechay',
            ])
            ->assertSessionHasErrors('vegetable_name');
    });

    it('rejects missing category_id', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), ['vegetable_name' => 'Kangkong'])
            ->assertSessionHasErrors('category_id');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->post(route('admin.vegetables.store'), ['category_id' => $this->category->id])
            ->assertSessionHasErrors('vegetable_name');
    });

    it('returns 403 for non-admin users', function () {
        actingAs($this->farmer)
            ->post(route('admin.vegetables.store'), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Kangkong',
            ])
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        post(route('admin.vegetables.store'), [])->assertRedirect(route('login'));
    });
});

// ─── Vegetable Update ─────────────────────────────────────────────────────────

describe('vegetable update', function () {
    it('updates a vegetable with a new image', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Pechay',
                'image' => UploadedFile::fake()->image('new.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect($this->vegetable->fresh()->getFirstMedia('vegetable_image'))->not->toBeNull();
    });

    it('rejects a non-image file on update', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Pechay',
                'image' => UploadedFile::fake()->create('doc.pdf', 512, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    });

    it('rejects missing name', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
            ])
            ->assertSessionHasErrors('vegetable_name');
    });

    it('allows saving with the same name (ignore self in unique check)', function () {
        actingAs($this->admin)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Pechay',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    });

    it('returns 403 for non-admin users and leaves the record intact', function () {
        actingAs($this->farmer)
            ->put(route('admin.vegetables.update', $this->vegetable), [
                'category_id' => $this->category->id,
                'vegetable_name' => 'Sneaky Update',
            ])
            ->assertForbidden();

        expect($this->vegetable->fresh()->vegetable_name)->toBe('Pechay');
    });

    it('redirects guests to login', function () {
        put(route('admin.vegetables.update', $this->vegetable), [])
            ->assertRedirect(route('login'));
    });
});

// ─── Vegetable Destroy ────────────────────────────────────────────────────────

describe('vegetable destroy', function () {
    it('deletes a vegetable with no varieties', function () {
        $empty = Vegetable::create(['category_id' => $this->category->id, 'vegetable_name' => 'Empty Veg']);

        actingAs($this->admin)
            ->delete(route('admin.vegetables.destroy', $empty))
            ->assertRedirect()
            ->assertSessionHas('flash.type', 'success');

        expect(Vegetable::find($empty->id))->toBeNull();
    });

    it('returns 403 for non-admin users and leaves the record intact', function () {
        actingAs($this->farmer)
            ->delete(route('admin.vegetables.destroy', $this->vegetable))
            ->assertForbidden();

        expect(Vegetable::find($this->vegetable->id))->not->toBeNull();
    });

    it('redirects guests to login', function () {
        delete(route('admin.vegetables.destroy', $this->vegetable))
            ->assertRedirect(route('login'));
    });
});
