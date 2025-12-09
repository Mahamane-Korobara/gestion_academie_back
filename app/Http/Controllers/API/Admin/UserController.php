<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateUserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Models\Etudiant;
use App\Models\Professeur;
use App\Models\AnneeAcademique;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use App\Notifications\UserCredentialsSent;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Liste de tous les utilisateurs (avec cache)
     */
    public function index(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 15);
        $roleFilter = $request->get('role');
        $search = $request->get('search');

        // Clé de cache unique selon les filtres
        $cacheKey = sprintf(
            'users:list:page:%d:per_page:%d:role:%s:search:%s',
            $page,
            $perPage,
            $roleFilter ?? 'all',
            $search ?? 'none'
        );

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($request, $roleFilter, $search, $perPage) {
            $query = User::with(['role', 'etudiant.filiere', 'etudiant.niveau', 'professeur']);

            // Filtre par rôle
            if ($roleFilter) {
                $query->whereHas('role', fn($q) => $q->where('name', $roleFilter));
            }

            // Recherche
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->latest()->paginate($perPage);

            return UserResource::collection($users);
        });
    }

    /**
     * Détails d'un utilisateur
     */
    public function show(User $user)
    {
        $cacheKey = CacheService::key('user', $user->id);

        return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($user) {
            $user->load(['role', 'etudiant.filiere', 'etudiant.niveau', 'professeur']);
            return new UserResource($user);
        });
    }

    /**
     * Créer un utilisateur
     */
    public function store(CreateUserRequest $request)
    {
        try {
            DB::beginTransaction();

            $userData = $request->only(['role_id', 'name', 'email', 'phone']);

            // Déterminer si c'est un étudiant ou un professeur
            $isEtudiantOuProf = $request->filled('etudiant') || $request->filled('professeur');

            if ($isEtudiantOuProf) {
                // Générer un mot de passe temporaire pour étudiants/profs
                $temporaryPassword = Str::password(10, true, true, false);
                $userData['password'] = Hash::make($temporaryPassword);
                $userData['must_change_password'] = true;
            } else {
                // Pour les admins (ou autres rôles internes) : mot de passe sans obligation
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                } else {
                    // Mot de passe par défaut pour admin (non temporaire)
                    $userData['password'] = Hash::make('admin123');
                }
                $userData['must_change_password'] = false; // pas d'obligation
            }

            $user = User::create($userData);

            // Créer le profil étudiant si nécessaire
            if ($request->filled('etudiant')) {
                $anneeActive = AnneeAcademique::active()->first();
                if (!$anneeActive) {
                    throw new \Exception('Aucune année académique active n\'est définie. Veuillez en créer une.');
                }

                Etudiant::create([
                    'user_id' => $user->id,
                    'annee_academique_id' => $anneeActive->id,
                    'date_inscription' => now(),
                    ...$request->etudiant,
                ]);
            }

            // Créer le profil professeur si nécessaire
            if ($request->filled('professeur')) {
                Professeur::create([
                    'user_id' => $user->id,
                    'email_professionnel' => $user->email,
                    'telephone' => $user->phone,
                    ...$request->professeur,
                ]);
            }

            DB::commit();

            CacheService::forgetUsers();

            // Envoyer les identifiants par email SEULEMENT pour étudiants/profs
            if ($isEtudiantOuProf) {
                $user->notify(new UserCredentialsSent($temporaryPassword));
            }

            return response()->json([
                'message' => 'Utilisateur créé avec succès',
                'user' => new UserResource($user->load(['role', 'etudiant', 'professeur'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création de l\'utilisateur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        $user->update($request->only(['name', 'email', 'phone', 'is_active']));

        // Invalider les caches
        CacheService::forgetUsers();
        CacheService::forget(CacheService::key('user', $user->id));

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(User $user)
    {
        $user->update([
            'password' => Hash::make('password123'),
            'must_change_password' => true,
        ]);

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès',
            'new_password' => 'password123',
        ]);
    }

    /**
     * Désactiver/Activer un compte
     */
    public function toggleActive(User $user)
    {
        $wasActive = $user->is_active;
        $user->is_active = !$wasActive;
        $user->save();

        // Invalider les caches
        CacheService::forgetUsers();
        CacheService::forget(CacheService::key('user', $user->id));

        // Si on réactive un compte précédemment désactivé
        if ($user->is_active && !$wasActive) {
            // Vérifier que c'est un étudiant ou un professeur pas un admin
            $userRole = $user->role?->name;
            if (in_array($userRole, ['etudiant', 'professeur'])) {
                $newPassword = \Illuminate\Support\Str::password(10, true, true, false);
                $user->update([
                    'password' => Hash::make($newPassword),
                    'must_change_password' => true, // déclenche CheckPasswordChange
                ]);

                // Envoyer les nouveaux identifiants par email
                $user->notify(new UserCredentialsSent($newPassword, isReactivation: true));
            }
        }

        return response()->json([
            'message' => $user->is_active 
                ? 'Compte réactivé. Un nouveau mot de passe a été envoyé par email.' 
                : 'Compte désactivé avec succès.',
            'user' => new UserResource($user->load(['role', 'etudiant', 'professeur'])),
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(Request $request, User $user)
    {
        // Empêcher la suppression de son propre compte
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte',
            ], 403);
        }

        $user->delete();

        // Invalider les caches
        CacheService::forgetUsers();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

}