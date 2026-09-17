<?php

use App\Http\Controllers\Shared\VegetableController;
use App\Http\Controllers\Shared\VegetableWatchController;
use Illuminate\Support\Facades\Route;

Route::get('/vegetables-options', [VegetableController::class, 'options'])->name('vegetables.options');

Route::middleware(['auth', 'verified', 'can:not-admin'])->group(function () {

    Route::prefix('vegetables')->name('vegetables.')->group(function () {
        Route::get('/', [VegetableController::class, 'index'])->name('index');
        Route::get('/{vegetable}', [VegetableController::class, 'show'])->name('show');

        Route::post('/{vegetable}/watch', [VegetableWatchController::class, 'store'])->name('watch');
        Route::delete('/{vegetable}/watch', [VegetableWatchController::class, 'destroy'])->name('unwatch');
    });

    Route::get('/watches', [VegetableWatchController::class, 'index'])->name('watches.index');
});