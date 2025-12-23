<?php

namespace App\Http\Controllers\API\Professeur;

use App\Http\Controllers\Controller;
use App\Services\ProfesseurDashboardService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProfesseurDashboardController extends Controller
{
    public function __construct(
        private ProfesseurDashboardService $service
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $cacheKey = "prof_dashboard_{$user->id}";

        $stats = Cache::remember($cacheKey, CacheService::SHORT_TTL, function () use ($user) {
            return $this->service->getStats($user);
        });

        return response()->json([
            'dashboard' => $stats,
            'last_updated' => now()->format('d/m/Y H:i')
        ]);
    }
}