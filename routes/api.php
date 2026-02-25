<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Contrôleurs Auth
use App\Http\Controllers\API\AuthController;

// Contrôleurs Admin
use App\Http\Controllers\API\Admin\AnneeAcademiqueController;
use App\Http\Controllers\API\Admin\SemestreController;
use App\Http\Controllers\API\Admin\FiliereController;
use App\Http\Controllers\API\Admin\NiveauController;
use App\Http\Controllers\API\Admin\CoursController;
use App\Http\Controllers\API\Admin\AffectationController;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Admin\InscriptionController;
use App\Http\Controllers\API\Admin\EvaluationController;
use App\Http\Controllers\API\Admin\NoteAdminController;
use App\Http\Controllers\API\Admin\BulletinController;
use App\Http\Controllers\API\Admin\DashboardController;
use App\Http\Controllers\API\Professeur\ProfesseurCoursController;
use App\Http\Controllers\API\Admin\EmploiDuTempsAdminController;
use App\Http\Controllers\API\Admin\AnnonceController;

// Contrôleurs Professeur
use App\Http\Controllers\API\Professeur\ProfesseurDashboardController;
use App\Http\Controllers\API\Professeur\NoteController;
use App\Http\Controllers\API\Professeur\EmploiDuTempsProfesseurController;
use App\Http\Controllers\API\Professeur\AnnonceProfesseurController;

// Contrôleurs Étudiant
use App\Http\Controllers\API\Etudiant\EtudiantController;
use App\Http\Controllers\API\Etudiant\EmploiDuTempsEtudiantController;

// Contrôleurs Communs
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\Professeur\DocumentController;
use App\Http\Controllers\API\Etudiant\DocumentEtudiantController;

// ============================================================================
// ROUTES PUBLIQUES
// ============================================================================
Route::post('/auth/login', [AuthController::class, 'login']);

// ============================================================================
// ROUTES AUTHENTIFIÉES (TOUS RÔLES)
// ============================================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Changement obligatoire de mot de passe
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    // Sessions actives
    Route::get('/auth/sessions', [AuthController::class, 'sessions']);
    Route::delete('/auth/sessions/{tokenId}', [AuthController::class, 'revokeSession']);

    // Profil utilisateur
    Route::post('/auth/update-profile', [AuthController::class, 'updateProfile']);

    // Messagerie commune
    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageController::class, 'index']);
        Route::get('/sent', [MessageController::class, 'sent']);
        Route::get('/conversation/{user}', [MessageController::class, 'conversation']);
        Route::get('/unread-count', [MessageController::class, 'unreadCount']);
        Route::post('/', [MessageController::class, 'store']);
        Route::post('/{message}/reply', [MessageController::class, 'reply']);
        Route::post('/{message}/mark-as-read', [MessageController::class, 'markAsRead']);
        Route::get('/{message}', [MessageController::class, 'show']);
        Route::delete('/{message}', [MessageController::class, 'destroy']);
    });
});

// ============================================================================
// ROUTES ADMIN
// ============================================================================
Route::middleware([
    'auth:sanctum',
    'role:admin',
    'check.user.active',
    'check.password.change'
])->prefix('admin')->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // -------------------------------------------------------------------------
    // Gestion des Années Académiques
    // -------------------------------------------------------------------------
    Route::prefix('annees-academiques')->group(function () {
        Route::get('/', [AnneeAcademiqueController::class, 'index']);
        Route::post('/', [AnneeAcademiqueController::class, 'store']);
        Route::get('/active', [AnneeAcademiqueController::class, 'active']);
        Route::get('/{anneeAcademique}', [AnneeAcademiqueController::class, 'show']);
        Route::put('/{anneeAcademique}', [AnneeAcademiqueController::class, 'update']);
        Route::delete('/{anneeAcademique}', [AnneeAcademiqueController::class, 'destroy']);
        Route::post('/{anneeAcademique}/activate', [AnneeAcademiqueController::class, 'activate']);
        Route::post('/{anneeAcademique}/close', [AnneeAcademiqueController::class, 'close']);
        // Route::post('/{anneeAcademique}/create-semestres', [AnneeAcademiqueController::class, 'createSemestres']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Semestres
    // -------------------------------------------------------------------------
    Route::prefix('semestres')->group(function () {
        Route::get('/', [SemestreController::class, 'index']);
        Route::post('/', [SemestreController::class, 'store']);
        Route::get('/active', [SemestreController::class, 'active']);
        Route::get('/{semestre}', [SemestreController::class, 'show']);
        Route::put('/{semestre}', [SemestreController::class, 'update']);
        Route::delete('/{semestre}', [SemestreController::class, 'destroy']);
        Route::post('/{semestre}/activate', [SemestreController::class, 'activate']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Filières
    // -------------------------------------------------------------------------
    Route::prefix('filieres')->group(function () {
        Route::get('/', [FiliereController::class, 'index']);
        Route::post('/', [FiliereController::class, 'store']);
        Route::get('/{filiere}', [FiliereController::class, 'show']);
        Route::put('/{filiere}', [FiliereController::class, 'update']);
        Route::delete('/{filiere}', [FiliereController::class, 'destroy']);
        Route::post('/{filiere}/create-standard-levels', [FiliereController::class, 'createStandardLevels']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Niveaux
    // -------------------------------------------------------------------------
    Route::prefix('niveaux')->group(function () {
        Route::get('/', [NiveauController::class, 'index']); // paginé + filtres
        Route::get('/all', [NiveauController::class, 'all']); // non paginé
        Route::post('/', [NiveauController::class, 'store']);
        Route::get('/{niveau}', [NiveauController::class, 'show']);
        Route::put('/{niveau}', [NiveauController::class, 'update']);
        Route::delete('/{niveau}', [NiveauController::class, 'destroy']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Cours
    // -------------------------------------------------------------------------
    Route::prefix('cours')->group(function () {
        Route::get('/', [CoursController::class, 'index']);
        Route::post('/', [CoursController::class, 'store']);
        Route::get('/{cours}', [CoursController::class, 'show']);
        Route::put('/{cours}', [CoursController::class, 'update']);
        Route::delete('/{cours}', [CoursController::class, 'destroy']);

        // Affectation des professeurs
        Route::post('/{cours}/affecter-professeurs', [AffectationController::class, 'affecterProfesseurs']);
        Route::delete('/{cours}/professeurs/{professeur}', [AffectationController::class, 'retirerProfesseur']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Utilisateurs (admin / professeurs / étudiants)
    // -------------------------------------------------------------------------
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::post('/{user}/reset-password', [UserController::class, 'resetPassword']);
    });

    // -------------------------------------------------------------------------
    // Gestion des Inscriptions
    // -------------------------------------------------------------------------
    Route::prefix('inscriptions')->group(function () {
        Route::get('/', [InscriptionController::class, 'index']);
        Route::post('/', [InscriptionController::class, 'store']);
        Route::post('/masse', [InscriptionController::class, 'inscriptionMasse']);
        Route::get('/etudiant/{etudiant}', [InscriptionController::class, 'inscriptionsEtudiant']);
        Route::get('/cours/{cours}', [InscriptionController::class, 'inscriptionsCours']);
        Route::delete('/{inscription}', [InscriptionController::class, 'destroy']);
    });

    // Endpoint rapide inscription d'un étudiant à tout un niveau
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
    Route::get('/evaluations/{evaluation}/notes', [NoteController::class, 'show']);
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
    Route::get('/evaluations', [ProfesseurCoursController::class, 'mesEvaluations']);
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
    Route::get('/bulletins/{bulletin}/pdf', [EtudiantController::class, 'downloadBulletin']);

    // -------------------------------------------------------------------------
    // Emploi du temps étudiant
    // -------------------------------------------------------------------------
    Route::prefix('emploi-du-temps')->group(function () {
        Route::get('/', [EmploiDuTempsEtudiantController::class, 'index']);
        Route::get('/semaine', [EmploiDuTempsEtudiantController::class, 'semaine']);
        Route::get('/jour', [EmploiDuTempsEtudiantController::class, 'jour']);
        Route::get('/resume', [EmploiDuTempsEtudiantController::class, 'resume']);
        Route::get('/prochains', [EmploiDuTempsEtudiantController::class, 'prochainsCours']);
    });

    // -------------------------------------------------------------------------
    // Annonces visibles pour l'étudiant
    // -------------------------------------------------------------------------
    Route::get('/annonces', [EtudiantController::class, 'annonces']);

    // -------------------------------------------------------------------------
    // Documents partagés au niveau étudiant
    // -------------------------------------------------------------------------
    Route::prefix('documents')->group(function () {
        Route::get('/', [DocumentEtudiantController::class, 'index']);
        Route::get('/{document}/download', [DocumentEtudiantController::class, 'download']);
    });
});
