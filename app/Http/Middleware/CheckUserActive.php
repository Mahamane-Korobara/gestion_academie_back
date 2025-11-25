<?php 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Veuillez contacter l\'administration.',
            ], 403);
        }

        return $next($request);
    }
}