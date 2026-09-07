<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DealerController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\RegistrationRequestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VegetableController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /* ---------- user management ---------- */

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/farmers/create', [UserController::class, 'createFarmerForm'])->name('farmers.create');
        Route::post('/farmers', [UserController::class, 'storeFarmer'])->name('farmers.store');

        Route::get('/dealers/create', [UserController::class, 'createDealerForm'])->name('dealers.create');
        Route::post('/dealers', [UserController::class, 'storeDealer'])->name('dealers.store');

        Route::patch('/{user}/phone', [UserController::class, 'updatePhone'])->name('update-phone');
        Route::post('/{user}/reset-pin', [UserController::class, 'resetPin'])->name('reset-pin');
    });

    Route::prefix('registration-requests')->name('registration-requests.')->group(function () {
        Route::get('/', [RegistrationRequestController::class, 'index'])->name('index');
        Route::post('/{registrationRequest}/approve', [RegistrationRequestController::class, 'approve'])->name('approve');
        Route::post('/{registrationRequest}/reject', [RegistrationRequestController::class, 'reject'])->name('reject');
    });

    /* ---------- vegetables ---------- */

    Route::prefix('vegetables')->name('vegetables.')->group(function () {
        Route::get('/', [VegetableController::class, 'index'])->name('index');
        Route::get('/{vegetable}', [VegetableController::class, 'show'])->name('show');

        Route::post('/', [VegetableController::class, 'store'])->name('store');
        Route::put('/{vegetable}', [VegetableController::class, 'update'])->name('update');
        Route::delete('/{vegetable}', [VegetableController::class, 'destroy'])->name('destroy');
    });

    /* ---------- farmers ---------- */

    Route::prefix('farmers')->name('farmers.')->group(function () {
        Route::get('/', [FarmerController::class, 'index'])->name('index');
        Route::delete('/{farmer}', [FarmerController::class, 'destroy'])->name('destroy');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/markers', [FarmerController::class, 'markers'])->name('markers');
            Route::get('/{farmer}/details', [FarmerController::class, 'details'])->name('details');
        });
    });

    /* ---------- dealers ---------- */

    Route::prefix('dealers')->name('dealers.')->group(function () {
        Route::get('/', [DealerController::class, 'index'])->name('index');
        Route::delete('/{dealer}', [DealerController::class, 'destroy'])->name('destroy');

        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/{dealer}/details', [DealerController::class, 'details'])->name('details');
        });
    });
});
