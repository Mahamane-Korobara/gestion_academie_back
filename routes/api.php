<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Admin\FiliereController;
use App\Http\Controllers\API\Admin\NiveauController;
use App\Http\Controllers\API\Admin\CoursController;
use App\Http\Controllers\API\Admin\DashboardController;
use App\Http\Controllers\API\Admin\AnneeAcademiqueController;
use App\Http\Controllers\API\Admin\SemestreController;
use App\Http\Controllers\API\Admin\InscriptionController;
use App\Http\Controllers\API\Admin\EvaluationController;
use App\Http\Controllers\API\Admin\NoteAdminController;
use App\Http\Controllers\API\Admin\AffectationController;
use App\Http\Controllers\API\Admin\BulletinController;
use App\Http\Controllers\API\Admin\EmploiDuTempsAdminController;
use App\Http\Controllers\API\Admin\AnnonceController;
use App\Http\Controllers\API\Professeur\NoteController;
use App\Http\Controllers\API\Professeur\EmploiDuTempsProfesseurController;
use App\Http\Controllers\API\Professeur\AnnonceProfesseurController;
use App\Http\Controllers\API\Professeur\ProfesseurCoursController;
use App\Http\Controllers\API\Professeur\ProfesseurDashboardController;
use App\Http\Controllers\API\Etudiant\EtudiantController;
use App\Http\Controllers\API\Etudiant\BulletinEtudiantController;
use App\Http\Controllers\API\Etudiant\EmploiDuTempsEtudiantController;
use App\Http\Controllers\API\Etudiant\AnnonceEtudiantController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\DocumentController;

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

        // Affectation professeurs =< cours
        Route::post('/{cours}/affecter-professeurs', [AffectationController::class, 'affecterProfesseurs']);
        Route::delete('/{cours}/professeurs/{professeur}', [AffectationController::class, 'retirerProfesseur']);
    });

    // -------------------------------------------------------------------------
    // Gestion des années académiques
    // -------------------------------------------------------------------------
    Route::prefix('annees-academiques')->group(function () {
        Route::get('/', [AnneeAcademiqueController::class, 'index']);
        Route::get('/active', [AnneeAcademiqueController::class, 'active']);
        Route::post('/', [AnneeAcademiqueController::class, 'store']);
        Route::get('/{anneeAcademique}', [AnneeAcademiqueController::class, 'show']);
        Route::put('/{anneeAcademique}', [AnneeAcademiqueController::class, 'update']);
        Route::patch('/{anneeAcademique}', [AnneeAcademiqueController::class, 'update']);
        Route::delete('/{anneeAcademique}', [AnneeAcademiqueController::class, 'destroy']);
        
        // Actions spécifiques
        Route::post('/{anneeAcademique}/activate', [AnneeAcademiqueController::class, 'activate']);
        Route::post('/{anneeAcademique}/close', [AnneeAcademiqueController::class, 'close']);
        // Route::post('/{anneeAcademique}/create-semestres', [AnneeAcademiqueController::class, 'createSemestres']);
    });

    // -------------------------------------------------------------------------
    // Gestion des semestres
    // -------------------------------------------------------------------------
    Route::prefix('semestres')->group(function () {
        Route::get('/active', [SemestreController::class, 'active']);
        Route::get('/', [SemestreController::class, 'index']); // Avec annee_academique_id
        Route::post('/', [SemestreController::class, 'store']);
        Route::get('/{semestre}', [SemestreController::class, 'show']);
        Route::put('/{semestre}', [SemestreController::class, 'update']);
        Route::patch('/{semestre}', [SemestreController::class, 'update']);
        Route::delete('/{semestre}', [SemestreController::class, 'destroy']);
        
        // Actions spécifiques
        Route::post('/{semestre}/activate', [SemestreController::class, 'activate']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Inscriptions
    // -------------------------------------------------------------------------
    Route::prefix('inscriptions')->group(function () {
        Route::get('/', [InscriptionController::class, 'index']);
        Route::post('/', [InscriptionController::class, 'store']);
        Route::post('/masse', [InscriptionController::class, 'inscriptionMasse']);
        Route::get('/etudiant/{etudiantId}', [InscriptionController::class, 'parEtudiant']);
        Route::get('/cours/{coursId}', [InscriptionController::class, 'parCours']);
        Route::delete('/{inscription}', [InscriptionController::class, 'destroy']);
    });

    // Inscription automatique par étudiant
    Route::post('/etudiants/{etudiant}/inscrire-cours-niveau', [InscriptionController::class, 'inscrireCoursNiveau']);

    // -------------------------------------------------------------------------
    // Gestion des Évaluations
    // -------------------------------------------------------------------------
    Route::prefix('evaluations')->group(function () {
        Route::get('/', [EvaluationController::class, 'all']); // toutes les éval.
        Route::get('/cours/{cours}', [EvaluationController::class, 'index']);
        Route::post('/cours/{cours}', [EvaluationController::class, 'store']);
        Route::get('/{evaluation}', [EvaluationController::class, 'show']);
        Route::put('/{evaluation}', [EvaluationController::class, 'update']);
        Route::delete('/{evaluation}', [EvaluationController::class, 'destroy']);
    });

    // -------------------------------------------------------------------------
    // Gestion des validations de notes
    // -------------------------------------------------------------------------
    Route::prefix('notes')->group(function () {
        Route::patch('/{note}/valider', [NoteAdminController::class, 'validerNotes']);
        Route::get('/en-attente', [NoteAdminController::class, 'notesEnAttente']);
        Route::post('/valider-masse', [NoteAdminController::class, 'validerMasse']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Bulletins
    // -------------------------------------------------------------------------
    Route::prefix('bulletins')->group(function () {
        // Bulletins semestriels
        Route::post('/etudiants/{etudiant}/semestres/{semestre}/generer', [BulletinController::class, 'genererSemestre']);
        Route::get('/etudiants/{etudiant}/semestres/{semestre}', [BulletinController::class, 'show']);

        // Bulletins annuels
        Route::post('/etudiants/{etudiant}/annees/{anneeAcademiqueId}/generer', [BulletinController::class, 'genererAnnuel']);
        Route::get('/etudiants/{etudiant}/annees/{anneeAcademiqueId}', [BulletinController::class, 'show']);

        // Liste globale
        Route::get('/', [BulletinController::class, 'index']);
    });

    // -------------------------------------------------------------------------
    // Gestion des emplois du temps
    // -------------------------------------------------------------------------
    Route::prefix('emplois-du-temps')->group(function () {
        Route::get('/', [EmploiDuTempsAdminController::class, 'index']);
        Route::post('/', [EmploiDuTempsAdminController::class, 'store']);
        Route::delete('/{emploiDuTemps}', [EmploiDuTempsAdminController::class, 'destroy']);
        
        // Emploi du temps par niveau
        Route::get('/niveau/{niveauId}', [EmploiDuTempsAdminController::class, 'emploiDuTempsNiveau']);
        
        // Recherche de ressources disponibles
        Route::get('/profs-disponibles', [EmploiDuTempsAdminController::class, 'professeursDisponibles']);
        Route::get('/cours-disponibles', [EmploiDuTempsAdminController::class, 'coursDisponibles']);
    });

    // -------------------------------------------------------------------------
    // Gestion des annonces
    // -------------------------------------------------------------------------
    Route::prefix('annonces')->group(function () {
        Route::get('/', [AnnonceController::class, 'index']);
        Route::post('/', [AnnonceController::class, 'store']);
        Route::get('/{annonce}', [AnnonceController::class, 'show']);
        Route::put('/{annonce}', [AnnonceController::class, 'update']);
        Route::delete('/{annonce}', [AnnonceController::class, 'destroy']);
        Route::post('/{annonce}/toggle-active', [AnnonceController::class, 'toggleActive']);
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

    // Dashboard
    Route::get('/dashboard', [ProfesseurDashboardController::class, 'index']);

    //Utilisateur - Annuaire
    Route::get('/directory', [App\Http\Controllers\API\Professeur\UserController::class, 'directory']);

    // -------------------------------------------------------------------------
    // Gestion des Saisies de notes
    // -------------------------------------------------------------------------
    Route::post('/evaluations/{evaluation}/notes', [NoteController::class, 'store']);

    // -------------------------------------------------------------------------
    // Gestion des emplois du temps
    // -------------------------------------------------------------------------
    Route::prefix('emploi-du-temps')->group(function () {
        Route::get('/semestres-disponibles', [EmploiDuTempsProfesseurController::class, 'semestresDisponibles']);
        Route::get('/', [EmploiDuTempsProfesseurController::class, 'index']);
        Route::get('/semaine', [EmploiDuTempsProfesseurController::class, 'semaine']);
        Route::get('/jour', [EmploiDuTempsProfesseurController::class, 'jour']);
        Route::get('/resume', [EmploiDuTempsProfesseurController::class, 'resume']);
        Route::get('/niveaux', [EmploiDuTempsProfesseurController::class, 'mesNiveaux']);
    });

    Route::get('/cours', [ProfesseurCoursController::class, 'mesCours']);
    Route::get('/form-options', [ProfesseurCoursController::class, 'getFormOptions']);

    // -------------------------------------------------------------------------
    // Gestion des annonces
    // -------------------------------------------------------------------------
    Route::get('/annonces', [AnnonceProfesseurController::class, 'index']);
    Route::post('/annonces', [AnnonceProfesseurController::class, 'store']);
    Route::get('/annonces/{annonce}', [AnnonceProfesseurController::class, 'show']);
    Route::put('/annonces/{annonce}', [AnnonceProfesseurController::class, 'update']);
    Route::delete('/annonces/{annonce}', [AnnonceProfesseurController::class, 'destroy']);
    Route::post('/annonces/{annonce}/toggle-active', [AnnonceProfesseurController::class, 'toggleActive']);

    // -------------------------------------------------------------------------
    // Gestion des messages de masse
    // -------------------------------------------------------------------------
    Route::post('/messages/masse', [MessageController::class, 'storeMasse']);

    // -------------------------------------------------------------------------
    // Gestion des documents
    // -------------------------------------------------------------------------
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentController::class, 'index']);      // Liste avec filtres
        Route::post('/', [DocumentController::class, 'store']);     // Envoyer
        Route::delete('/{document}', [DocumentController::class, 'destroy']); // Supprimer
        Route::get('/{document}/download', [DocumentController::class, 'download']); // Télécharger
    });
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
    // Dashboard principal
    Route::get('/dashboard', [EtudiantController::class, 'dashboard']);
    
    // Bulletins académiques
    Route::get('/bulletins', [EtudiantController::class, 'bulletins']);
    
    // Notes détaillées
    Route::get('/notes', [EtudiantController::class, 'notes']);
    
    // Cours inscrits
    Route::get('/cours', [EtudiantController::class, 'cours']);

    // Téléchargement PDF d'un bulletin
    Route::get('/bulletins/{bulletinId}/pdf', [BulletinEtudiantController::class, 'telechargerPDF']);
    
    // -------------------------------------------------------------------------
    // Gestion des emplois du temps
    // -------------------------------------------------------------------------
    Route::prefix('emploi-du-temps')->group(function () {
        Route::get('/', [EmploiDuTempsEtudiantController::class, 'index']);
        Route::get('/semaine', [EmploiDuTempsEtudiantController::class, 'semaine']);
        Route::get('/jour', [EmploiDuTempsEtudiantController::class, 'jour']);
        Route::get('/resume', [EmploiDuTempsEtudiantController::class, 'resume']);
        Route::get('/prochains', [EmploiDuTempsEtudiantController::class, 'prochains']);
    });

    // -------------------------------------------------------------------------
    // Gestion des annonces
    // -------------------------------------------------------------------------
    Route::get('/annonces', [AnnonceEtudiantController::class, 'index']);

    // -------------------------------------------------------------------------
    // Gestion des documents
    // -------------------------------------------------------------------------
    Route::prefix('documents')->group(function () {
    Route::get('/', [DocumentController::class, 'index']);      // Liste avec filtres
    Route::get('/{document}/download', [DocumentController::class, 'download']); // Télécharger
});

});

// ============================================================================
// ROUTES AUTHENTIFIÉES communes à tous les rôles
// ============================================================================
Route::middleware(['auth:sanctum', 'check.user.active', 'check.password.change'])->group(function () {
    
    // Messagerie commune à tous les rôles
    Route::prefix('messages')->group(function () {
         Route::get('/conversation/{userId}', [MessageController::class, 'conversation']);
         Route::get('/sent', [MessageController::class, 'sent']);
        Route::get('/', [MessageController::class, 'index']); 
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
        Route::post('/', [MessageController::class, 'store']);
        Route::get('/{message}', [MessageController::class, 'show']);
        Route::post('/{id}/reply', [MessageController::class, 'reply']);
        Route::post('/{message}/mark-as-read', [MessageController::class, 'markAsRead']);
        Route::delete('/{message}', [MessageController::class, 'destroy']);
    });
});