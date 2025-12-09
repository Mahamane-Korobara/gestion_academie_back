<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        // Routes exclues
        $excludedRoutes = [
            'api/auth/change-password',
            'api/auth/logout',
            'api/auth/me',
        ];

        $user = $request->user();

        // Si l'utilisateur est un admin, on ne lui applique pas la règle
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Pour les non-admins : appliquer la règle
        if ($user 
            && $user->must_change_password 
            && !in_array($request->path(), $excludedRoutes)) {
            
            return response()->json([
                'message' => 'Vous devez changer votre mot de passe avant de continuer.',
                'must_change_password' => true,
            ], 403);
        }

        return $next($request);
    }
}