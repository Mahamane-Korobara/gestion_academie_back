<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Admin\FiliereController;
use App\Http\Controllers\API\Admin\NiveauController;
use App\Http\Controllers\API\Admin\CoursController;
use App\Http\Controllers\API\Admin\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ============================================================================
// ROUTES PUBLIQUES
// ============================================================================
Route::prefix('auth')->group(function () {
    // Login avec rate limiting (3 tentatives par minute)
    Route::middleware(['throttle:3,1'])->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });
});

// ============================================================================
// ROUTES AUTHENTIFIÉES (tous les utilisateurs connectés)
// ============================================================================
Route::middleware(['auth:sanctum', 'check.user.active'])->prefix('auth')->group(function () {
    // Informations utilisateur
    Route::get('/me', [AuthController::class, 'me']);
    
    // Déconnexion
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    
    // Changement de mot de passe (accessible même si must_change_password = true)
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // Routes nécessitant un mot de passe changé
    Route::middleware('check.password.change')->group(function () {
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
        Route::get('/sessions', [AuthController::class, 'activeSessions']);
        Route::delete('/sessions/{tokenId}', [AuthController::class, 'revokeSession']);
    });
});

// ============================================================================
// ROUTES ADMINISTRATEUR
// ============================================================================
Route::middleware([
    'auth:sanctum',
    'role:admin',
    'check.user.active',
    'check.password.change'
])->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // -------------------------------------------------------------------------
    // Gestion des utilisateurs
    // -------------------------------------------------------------------------
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::patch('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        
        // Actions spécifiques
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword']);
        Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });

    // -------------------------------------------------------------------------
    // Gestion des filières
    // -------------------------------------------------------------------------
    Route::prefix('filieres')->group(function () {
        Route::get('/', [FiliereController::class, 'index']);
        Route::post('/', [FiliereController::class, 'store']);
        Route::get('/{filiere}', [FiliereController::class, 'show']);
        Route::put('/{filiere}', [FiliereController::class, 'update']);
        Route::patch('/{filiere}', [FiliereController::class, 'update']);
        Route::delete('/{filiere}', [FiliereController::class, 'destroy']);
        
        // Création automatique des niveaux
        Route::post('/{filiere}/create-standard-levels', [NiveauController::class, 'createStandardLevels']);
    });

    // -------------------------------------------------------------------------
    // Gestion des niveaux
    // -------------------------------------------------------------------------
    Route::prefix('niveaux')->group(function () {
        Route::get('/all', [NiveauController::class, 'all']); // Tous les niveaux
        Route::get('/', [NiveauController::class, 'index']); // Par filière
        Route::post('/', [NiveauController::class, 'store']);
        Route::get('/{niveau}', [NiveauController::class, 'show']);
        Route::put('/{niveau}', [NiveauController::class, 'update']);
        Route::patch('/{niveau}', [NiveauController::class, 'update']);
        Route::delete('/{niveau}', [NiveauController::class, 'destroy']);
    });

    // -------------------------------------------------------------------------
    // Gestion des cours
    // -------------------------------------------------------------------------
    Route::prefix('cours')->group(function () {
        Route::get('/', [CoursController::class, 'index']);
        Route::post('/', [CoursController::class, 'store']);
        Route::get('/{cours}', [CoursController::class, 'show']);
        Route::put('/{cours}', [CoursController::class, 'update']);
        Route::patch('/{cours}', [CoursController::class, 'update']);
        Route::delete('/{cours}', [CoursController::class, 'destroy']);
    });
});

// ============================================================================
// ROUTES PROFESSEUR
// ============================================================================
Route::middleware([
    'auth:sanctum',
    'role:professeur',
    'check.user.active',
    'check.password.change'
])->prefix('professeur')->group(function () {
    // Routes professeur
});

// ============================================================================
// ROUTES ÉTUDIANT
// ============================================================================
Route::middleware([
    'auth:sanctum',
    'role:etudiant',
    'check.user.active',
    'check.password.change'
])->prefix('etudiant')->group(function () {
     // Routes étudiant
});