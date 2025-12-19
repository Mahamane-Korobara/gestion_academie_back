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
use App\Services\LogService;
use App\Enums\ActionLog;
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

        $cacheKey = sprintf(
            'users:list:page:%d:per_page:%d:role:%s:search:%s',
            $page,
            $perPage,
            $roleFilter ?? 'all',
            $search ?? 'none'
        );

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($request, $roleFilter, $search, $perPage) {
            $query = User::with(['role', 'etudiant.filiere', 'etudiant.niveau', 'professeur']);

            if ($roleFilter) {
                $query->whereHas('role', fn($q) => $q->where('name', $roleFilter));
            }

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
            return DB::transaction(function () use ($request) {
                $userData = $request->only(['role_id', 'name', 'email', 'phone']);
                $isEtudiantOuProf = $request->filled('etudiant') || $request->filled('professeur');

                if ($isEtudiantOuProf) {
                    $temporaryPassword = Str::password(10, true, true, false);
                    $userData['password'] = Hash::make($temporaryPassword);
                    $userData['must_change_password'] = true;
                } else {
                    $userData['password'] = Hash::make($request->password ?? 'admin123');
                    $userData['must_change_password'] = false;
                }

                $user = User::create($userData);

                // Profil étudiant
                if ($request->filled('etudiant')) {
                    $anneeActive = AnneeAcademique::active()->first();
                    if (!$anneeActive) {
                        throw new \Exception('Aucune année académique active définie.');
                    }

                    Etudiant::create([
                        'user_id' => $user->id,
                        'annee_academique_id' => $anneeActive->id,
                        'date_inscription' => now(),
                        ...$request->etudiant,
                    ]);
                }

                // Profil professeur
                if ($request->filled('professeur')) {
                    Professeur::create([
                        'user_id' => $user->id,
                        'email_professionnel' => $user->email,
                        'telephone' => $user->phone,
                        ...$request->professeur,
                    ]);
                }

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::CREATE,
                    "Création de l'utilisateur : {$user->name} (Email: {$user->email})",
                    $user,
                    null,
                    $user->toArray()
                );

                CacheService::forgetUsers();

                if ($isEtudiantOuProf) {
                    $user->notify(new UserCredentialsSent($temporaryPassword));
                }

                return response()->json([
                    'message' => 'Utilisateur créé avec succès',
                    'user' => new UserResource($user->load(['role', 'etudiant', 'professeur'])),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function update(Request $request, User $user)
    {
        try {
            return DB::transaction(function () use ($request, $user) {
                $oldValues = $user->toArray();
                $user->update($request->only(['name', 'email', 'phone', 'is_active']));

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Modification du profil de : {$user->name}",
                    $user,
                    $oldValues,
                    $user->fresh()->toArray()
                );

                CacheService::forgetUsers();
                CacheService::forget(CacheService::key('user', $user->id));

                return response()->json([
                    'message' => 'Utilisateur mis à jour avec succès',
                    'user' => new UserResource($user->fresh()),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function resetPassword(User $user)
    {
        try {
            return DB::transaction(function () use ($user) {
                $user->update([
                    'password' => Hash::make('password123'),
                    'must_change_password' => true,
                ]);

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    "Réinitialisation du mot de passe pour : {$user->email}",
                    $user
                );

                return response()->json([
                    'message' => 'Mot de passe réinitialisé avec succès',
                    'new_password' => 'password123',
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Désactiver/Activer un compte
     */
    public function toggleActive(User $user)
    {
        try {
            return DB::transaction(function () use ($user) {
                $wasActive = $user->is_active;
                $user->is_active = !$wasActive;
                $user->save();

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::UPDATE,
                    ($user->is_active ? "Réactivation" : "Désactivation") . " du compte de {$user->name}",
                    $user,
                    ['is_active' => $wasActive],
                    ['is_active' => $user->is_active]
                );

                CacheService::forgetUsers();
                CacheService::forget(CacheService::key('user', $user->id));

                if ($user->is_active && !$wasActive) {
                    $userRole = $user->role?->name;
                    if (in_array($userRole, ['etudiant', 'professeur'])) {
                        $newPassword = Str::password(10, true, true, false);
                        $user->update([
                            'password' => Hash::make($newPassword),
                            'must_change_password' => true,
                        ]);
                        $user->notify(new UserCredentialsSent($newPassword, isReactivation: true));
                    }
                }

                return response()->json([
                    'message' => $user->is_active 
                        ? 'Compte réactivé. Un nouveau mot de passe a été envoyé.' 
                        : 'Compte désactivé avec succès.',
                    'user' => new UserResource($user->load(['role', 'etudiant', 'professeur'])),
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Supprimer un utilisateur
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Action impossible sur soi-même'], 403);
        }

        try {
            return DB::transaction(function () use ($user) {
                $userName = $user->name;
                $oldData = $user->toArray();

                // --- LOG SERVICE ---
                LogService::write(
                    ActionLog::DELETE,
                    "Suppression définitive de l'utilisateur : {$userName}",
                    $user,
                    $oldData
                );

                $user->delete();
                CacheService::forgetUsers();

                return response()->json(['message' => 'Utilisateur supprimé avec succès']);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }
}