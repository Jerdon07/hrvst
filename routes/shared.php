<?php

use App\Http\Controllers\Shared\DealerController;
use App\Http\Controllers\Shared\FarmerController;
use App\Http\Controllers\Shared\VegetableController;
use App\Http\Controllers\Shared\VegetableWatchController;
use Illuminate\Support\Facades\Route;

Route::get('/vegetables-options', [VegetableController::class, 'options'])->name('vegetables.options');

Route::middleware(['can:not-admin'])->group(function () {

    Route::prefix('vegetables')->name('vegetables.')->group(function () {
        Route::get('/', [VegetableController::class, 'index'])->name('index');
        Route::get('/{vegetable}', [VegetableController::class, 'show'])->name('show');

        /** watch vegetable prescription */
        Route::post('/{vegetable}/watch', [VegetableWatchController::class, 'store'])->name('watch');
        Route::delete('/{vegetable}/watch', [VegetableWatchController::class, 'destroy'])->name('unwatch');
    });
    
    Route::get('/watches', [VegetableWatchController::class, 'index'])->name('watches.index');

    // ── Public profile viewing (farmer/dealer viewing each other) ──────────
    Route::get('/farmers/{farmer}', [FarmerController::class, 'show'])->name('farmers.show');
    Route::get('/dealers/{dealer}', [DealerController::class, 'show'])->name('dealers.show');
});