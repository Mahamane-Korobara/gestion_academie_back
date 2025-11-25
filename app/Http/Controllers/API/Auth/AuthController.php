<?php 

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\LogActivite;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion de l'utilisateur
     */
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        // Vérifier le mot de passe
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Vérifier si le compte est actif
        if (!$user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administration.',
            ], 403);
        }

        // Mettre à jour les informations de connexion
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Créer le token d'authentification
        $token = $user->createToken('auth-token')->plainTextToken;
        $user->tokens()->skip(5)->delete();
        // Enregistrer le log de connexion
        LogActivite::create([
            'user_id' => $user->id,
            'action' => ActionLog::LOGIN,
            'description' => 'Connexion réussie',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Connexion réussie',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Déconnexion de l'utilisateur
     */
    public function logout(Request $request)
    {
        // Enregistrer le log de déconnexion
        LogActivite::create([
            'user_id' => $request->user()->id,
            'action' => ActionLog::LOGOUT,
            'description' => 'Déconnexion',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Supprimer le token actuel
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Déconnexion de tous les appareils
     */
    public function logoutAll(Request $request)
    {
        // Supprimer tous les tokens
        $request->user()->tokens()->delete();

        // Enregistrer le log
        LogActivite::create([
            'user_id' => $request->user()->id,
            'action' => ActionLog::LOGOUT,
            'description' => 'Déconnexion de tous les appareils',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Déconnexion réussie de tous les appareils',
        ]);
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = $request->user();

        // Vérifier l'ancien mot de passe
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        // Mettre à jour le mot de passe
        $user->update([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
        ]);

        // Enregistrer le log
        LogActivite::create([
            'user_id' => $user->id,
            'action' => ActionLog::UPDATE,
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => 'Changement de mot de passe',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Mot de passe changé avec succès',
        ]);
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $oldValues = $user->only(['name', 'email', 'phone']);

        // Gérer l'upload de l'avatar
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $request->merge(['avatar' => $path]);
        }

        $user->update($request->validated());

        // Enregistrer le log
        LogActivite::create([
            'user_id' => $user->id,
            'action' => ActionLog::UPDATE,
            'model_type' => User::class,
            'model_id' => $user->id,
            'description' => 'Mise à jour du profil',
            'old_values' => $oldValues,
            'new_values' => $user->only(['name', 'email', 'phone']),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    /**
     * Sessions actives de l'utilisateur
     */
      public function activeSessions(Request $request)
    {
        $currentToken = $request->user()->currentAccessToken();
        $tokens = $request->user()->tokens;

        return response()->json([
            'sessions' => $tokens->map(function ($token) use ($currentToken) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at?->format('Y-m-d H:i:s'),
                    'created_at' => $token->created_at->format('Y-m-d H:i:s'),
                    'is_current' => $token->id === $currentToken->id,
                ];
            }),
        ]);
    }

    /**
     * Supprimer une session spécifique
     */
    public function revokeSession(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return response()->json([
            'message' => 'Session supprimée avec succès',
        ]);
    }
}
