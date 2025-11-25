<?php 

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Routes publiques (sans authentification)
Route::middleware(['throttle:3,1'])->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
}); // 3 tentatives par minutes

// Routes protégées (avec authentification)
Route::middleware(['auth:sanctum', 'check.user.active'])->prefix('auth')->group(function () {
    // Informations utilisateur
    Route::get('/me', [AuthController::class, 'me']);
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    
    // Changement de mot de passe (accessible même si must_change_password = true)
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // Gestion du profil
    Route::post('/update-profile', [AuthController::class, 'updateProfile'])
        ->middleware('check.password.change');
    
    // Gestion des sessions
    Route::get('/sessions', [AuthController::class, 'activeSessions'])
        ->middleware('check.password.change');
    Route::delete('/sessions/{tokenId}', [AuthController::class, 'revokeSession'])
        ->middleware('check.password.change');
});
