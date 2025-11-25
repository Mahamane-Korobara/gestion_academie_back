<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Admin\FiliereController;
use App\Http\Controllers\API\Admin\CoursController;

// Routes publiques
Route::middleware(['throttle:3,1'])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Routes protégées par auth:sanctum
Route::middleware(['auth:sanctum', 'check.user.active'])->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile'])
        ->middleware('check.password.change');
    Route::get('/sessions', [AuthController::class, 'activeSessions'])
        ->middleware('check.password.change');
    Route::delete('/sessions/{tokenId}', [AuthController::class, 'revokeSession'])
        ->middleware('check.password.change');
});

// Routes admin
Route::middleware([
        'auth:sanctum',
        'role:admin',
        'check.user.active',
        'check.password.change'
    ])->prefix('admin')->group(function () {

    // Utilisateurs
    Route::apiResource('users', UserController::class);
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive']);

    // Filières
    Route::apiResource('filieres', FiliereController::class);

    // Cours
    Route::apiResource('cours', CoursController::class);
});
