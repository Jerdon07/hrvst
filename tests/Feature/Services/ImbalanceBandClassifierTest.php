<?php

use App\Enums\Analytics\ImbalanceBand;
use App\Services\Vegetable\Analytics\ImbalanceBandClassifier;

beforeEach(function () {
    $this->classifier = new ImbalanceBandClassifier;
});

it('classifies balanced at the midpoint', function () {
    expect($this->classifier->classify(0.0))->toBe(ImbalanceBand::Balanced);
});

it('classifies balanced exactly at the oversupply threshold (boundary is exclusive)', function () {
    expect($this->classifier->classify(0.20))->toBe(ImbalanceBand::Balanced);
});

it('classifies oversupply just above the threshold', function () {
    expect($this->classifier->classify(0.2001))->toBe(ImbalanceBand::Oversupply);
});

it('classifies balanced exactly at the undersupply threshold (boundary is exclusive)', function () {
    expect($this->classifier->classify(-0.20))->toBe(ImbalanceBand::Balanced);
});

it('classifies undersupply just below the threshold', function () {
    expect($this->classifier->classify(-0.2001))->toBe(ImbalanceBand::Undersupply);
});

it('derives the ratio from raw supply/demand volumes', function () {
    expect($this->classifier->ratioFromVolumes(150.0, 100.0))->toBe(0.5)
        ->and($this->classifier->ratioFromVolumes(50.0, 100.0))->toBe(-0.5);
});

it('floors demand at 1.0 to avoid division by zero', function () {
    expect($this->classifier->ratioFromVolumes(50.0, 0.0))->toBe(50.0);
});

it('classifies a realistic oversupply ratio derived from volumes', function () {
    expect($this->classifier->classify($this->classifier->ratioFromVolumes(150.0, 100.0)))
        ->toBe(ImbalanceBand::Oversupply);
});
