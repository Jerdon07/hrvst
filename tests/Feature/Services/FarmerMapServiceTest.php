<?php

use App\Enums\PostItemStatus;
use App\Services\Admin\FarmerMapService;

beforeEach(function () {
    $this->service = app(FarmerMapService::class);
});

it('lists municipality options with a "Municipality, Province" label', function () {
    createBarangay('Benguet', 'La Trinidad', 'Poblacion');

    $options = $this->service->getMunicipalityOptions();

    expect($options)->toHaveCount(1)
        ->and($options[0]['label'])->toBe('La Trinidad, Benguet');
});

it('supply options only include vegetables with an ongoing supply post', function () {
    $withSupply = createVegetable();
    $withoutSupply = createVegetable();
    createSupplyPost(createFarmerUser(), $withSupply);

    $ids = collect($this->service->getSupplyOptions())->flatten(1)->pluck('id')->all();

    expect($ids)->toContain($withSupply->id)
        ->and($ids)->not->toContain($withoutSupply->id);
});

it('returns farmers with their ongoing supply count', function () {
    $farmer = createFarmerUser();
    createSupplyPost($farmer, createVegetable());

    $result = $this->service->getFarmersForMap();

    expect($result)->toHaveCount(1)
        ->and($result[0]['ongoing_supplies_count'])->toBe(1);
});

it('filters farmers by municipality_id', function () {
    $barangayA = createBarangay('Benguet', 'La Trinidad', 'Pico');
    $barangayB = createBarangay('Benguet', 'Tublay', 'Ambassador');

    createFarmerUser($barangayA);
    createFarmerUser($barangayB);

    expect($this->service->getFarmersForMap(municipalityId: $barangayA->municipality_id))->toHaveCount(1);
});

it('filters farmers by vegetable_id, matching only an ongoing supply of that vegetable', function () {
    $vegetable = createVegetable();
    $other = createVegetable();

    $match = createFarmerUser();
    createSupplyPost($match, $vegetable);

    $noMatch = createFarmerUser();
    createSupplyPost($noMatch, $other);

    expect($this->service->getFarmersForMap(vegetableId: $vegetable->id))->toHaveCount(1);
});

it('filters farmers by map bounds', function () {
    $barangay = createBarangay();
    $inside = createFarmerUser($barangay); // lat/lng from createFarmerUser default
    $outside = createFarmerUser($barangay);
    $outside->farmerProfile->update(['latitude' => 5.0, 'longitude' => 5.0]);

    $result = $this->service->getFarmersForMap(bounds: [
        'north' => 17.0, 'south' => 16.0, 'east' => 121.0, 'west' => 120.0,
    ]);

    $ids = collect($result)->pluck('id');
    expect($ids)->toContain($inside->farmerProfile->id)
        ->and($ids)->not->toContain($outside->farmerProfile->id);
});

it('excludes expired supply items from the summary count', function () {
    $farmer = createFarmerUser();
    $post = createSupplyPost($farmer, createVegetable());
    $post->postItems()->update(['status' => PostItemStatus::Expired]);

    expect($this->service->getFarmersForMap()[0]['ongoing_supplies_count'])->toBe(0);
});
