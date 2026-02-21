<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAnneeAcademiqueRequest;
use App\Http\Requests\Admin\UpdateAnneeAcademiqueRequest;
use App\Http\Resources\Admin\AnneeAcademiqueResource;
use App\Models\AnneeAcademique;
use App\Services\CacheService;
use App\Services\LogService;
use App\Enums\ActionLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnneeAcademiqueController extends Controller
{
    public function index()
    {
        $cacheKey = 'annees_academiques:all';

        return Cache::remember($cacheKey, CacheService::SHORT_TTL, function () {
            $annees = AnneeAcademique::with(['semestres.anneeAcademique'])
                ->withCount(['semestres', 'etudiants', 'cours'])
                ->orderByDesc('date_debut')
                ->get();

            return AnneeAcademiqueResource::collection($annees);
        });
    }

    public function active()
    {
        $cacheKey = 'annee_academique:active';

        return Cache::remember($cacheKey, CacheService::LONG_TTL, function () {
            $annee = AnneeAcademique::active()
                ->with('semestres')
                ->withCount(['etudiants', 'cours'])
                ->first();

            if (!$annee) {
                return response()->json(['message' => 'Aucune année académique active', 'data' => null], 404);
            }

            return new AnneeAcademiqueResource($annee);
        });
    }

    public function show(AnneeAcademique $anneeAcademique)
    {
        $anneeAcademique->load('semestres')
            ->loadCount(['etudiants', 'cours', 'inscriptions']);

        return new AnneeAcademiqueResource($anneeAcademique);
    }

    public function store(CreateAnneeAcademiqueRequest $request)
    {
        try {
            DB::beginTransaction();

            if ($request->is_active) {
                AnneeAcademique::deactivateAll();
            }

            $annee = AnneeAcademique::create($request->validated());

            LogService::write(
                ActionLog::CREATE,
                "Création de l'année académique : {$annee->annee}",
                $annee,
                null,
                $annee->toArray()
            );

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json([
                'message' => 'Année académique créée avec succès',
                'annee'   => new AnneeAcademiqueResource($annee),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la création', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateAnneeAcademiqueRequest $request, AnneeAcademique $anneeAcademique)
    {
        try {
            DB::beginTransaction();

            $oldValues = $anneeAcademique->toArray();

            if ($request->has('is_active') && $request->is_active) {
                AnneeAcademique::deactivateAll();
            }

            $anneeAcademique->update($request->validated());

            LogService::write(
                ActionLog::UPDATE,
                "Mise à jour de l'année académique : {$anneeAcademique->annee}",
                $anneeAcademique,
                $oldValues,
                $anneeAcademique->fresh()->toArray()
            );

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
            ]);

            return response()->json([
                'message' => 'Année académique mise à jour avec succès',
                'annee'   => new AnneeAcademiqueResource($anneeAcademique),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la mise à jour', 'error' => $e->getMessage()], 500);
        }
    }

    public function activate(AnneeAcademique $anneeAcademique)
    {
        try {
            DB::beginTransaction();

            if ($anneeAcademique->is_cloturee) {
                return response()->json(['message' => 'Impossible d\'activer une année clôturée'], 422);
            }

            AnneeAcademique::deactivateAll();
            $anneeAcademique->update(['is_active' => true]);

            LogService::write(
                ActionLog::VALIDATE,
                "Activation de l'année académique : {$anneeAcademique->annee}",
                $anneeAcademique
            );

            DB::commit();

            CacheService::forget(['annees_academiques:all', 'annee_academique:active']);

            return response()->json([
                'message' => 'Année académique activée avec succès',
                'annee'   => new AnneeAcademiqueResource($anneeAcademique),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'activation', 'error' => $e->getMessage()], 500);
        }
    }

    public function close(AnneeAcademique $anneeAcademique)
    {
        try {
            DB::beginTransaction();

            $anneeAcademique->update(['is_cloturee' => true, 'is_active' => false]);

            LogService::write(
                ActionLog::VALIDATE,
                "Clôture de l'année académique : {$anneeAcademique->annee}",
                $anneeAcademique
            );

            DB::commit();

            CacheService::forget(['annees_academiques:all', 'annee_academique:active']);

            return response()->json([
                'message' => 'Année académique clôturée avec succès',
                'annee'   => new AnneeAcademiqueResource($anneeAcademique),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la clôture', 'error' => $e->getMessage()], 500);
        }
    }

    // ✅ createSemestres supprimé — les semestres sont créés manuellement

    public function destroy(AnneeAcademique $anneeAcademique)
    {
        if ($anneeAcademique->etudiants()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer une année avec des étudiants inscrits'], 422);
        }

        if ($anneeAcademique->is_active) {
            return response()->json(['message' => 'Impossible de supprimer l\'année académique active'], 422);
        }

        try {
            DB::beginTransaction();

            LogService::write(
                ActionLog::DELETE,
                "Suppression de l'année académique : {$anneeAcademique->annee}",
                $anneeAcademique,
                $anneeAcademique->toArray()
            );

            $anneeAcademique->delete();

            DB::commit();

            CacheService::forget([
                'annees_academiques:all',
                'annee_academique:active',
                CacheService::KEYS['stats_dashboard'],
            ]);

            return response()->json(['message' => 'Année académique supprimée avec succès']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }
}