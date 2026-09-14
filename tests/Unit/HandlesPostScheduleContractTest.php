<?php

use App\Http\Controllers\Concerns\HandlesPostSchedule;
use App\Http\Controllers\Dealer\Schedule\DemandController;
use App\Http\Controllers\Farmer\Schedule\SupplyController;
use App\Http\Requests\Post\UpdatePostRequest;

it('implements its own update() rather than silently inheriting a trait default', function (string $class) {
    $declaringClass = (new ReflectionClass($class))->getMethod('update')->getDeclaringClass()->getName();

    expect($declaringClass)->toBe($class);
})->with([SupplyController::class, DemandController::class]);

it('type-hints a concrete, instantiable UpdatePostRequest subclass — never the abstract base', function (string $class) {
    $requestParam = (new ReflectionClass($class))->getMethod('update')->getParameters()[0];
    $requestType = new ReflectionClass($requestParam->getType()->getName());

    expect($requestType->isSubclassOf(UpdatePostRequest::class))->toBeTrue()
        ->and($requestType->isAbstract())->toBeFalse();
})->with([SupplyController::class, DemandController::class]);