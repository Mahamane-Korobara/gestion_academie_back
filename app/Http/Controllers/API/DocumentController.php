<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DocumentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Lister les documents (avec cache)
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $page = $request->input('page', 1);

        // Paramètres de filtre
        $filiereId = $request->filled('filiere_id') ? $request->filiere_id : 'all';
        $niveauId = $request->filled('niveau_id') ? $request->niveau_id : 'all';
        $coursId = $request->filled('cours_id') ? $request->cours_id : 'all';

        if ($user->isEtudiant()) {
            // Clé de cache pour étudiant
            $cacheKey = CacheService::key(
                'documents_etudiant', 
                $user->id, 
                $filiereId, 
                $niveauId, 
                $coursId, 
                $page
            );

            return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($user, $request) {
                $query = Document::actifs()
                    ->where('filiere_id', $user->etudiant->filiere_id)
                    ->where('niveau_id', $user->etudiant->niveau_id)
                    ->whereHas('cours', function ($q) use ($user) {
                        $q->whereIn('id', $user->etudiant->inscriptions->pluck('cours_id'));
                    });

                // Appliquer les filtres si présents
                if ($request->filled('filiere_id')) {
                    $query->where('filiere_id', $request->filiere_id);
                }
                if ($request->filled('niveau_id')) {
                    $query->where('niveau_id', $request->niveau_id);
                }
                if ($request->filled('cours_id')) {
                    $query->where('cours_id', $request->cours_id);
                }
                if ($request->filled('type')) {
                    $query->where('type', $request->type);
                }

                return DocumentResource::collection(
                    $query->orderByDesc('created_at')->paginate(20)
                );
            });
        } else {
            // Clé de cache pour professeur
            $cacheKey = CacheService::key(
                'documents_prof', 
                $user->id, 
                $filiereId, 
                $niveauId, 
                $coursId, 
                $page
            );

            return Cache::remember($cacheKey, CacheService::DEFAULT_TTL, function () use ($user, $request) {
                $query = Document::where('expediteur_id', $user->id);

                // Appliquer les filtres
                if ($request->filled('filiere_id')) {
                    $query->where('filiere_id', $request->filiere_id);
                }
                if ($request->filled('niveau_id')) {
                    $query->where('niveau_id', $request->niveau_id);
                }
                if ($request->filled('cours_id')) {
                    $query->where('cours_id', $request->cours_id);
                }
                if ($request->filled('type')) {
                    $query->where('type', $request->type);
                }

                return DocumentResource::collection(
                    $query->orderByDesc('created_at')->paginate(20)
                );
            });
        }
    }

    /**
     * Télécharger un document
     */
    public function download(Document $document, Request $request)
    {
        if ($request->user()->isEtudiant()) {
            $this->authorize('view', $document);
        } else {
            $this->authorize('viewProfesseur', $document);
        }

        $filePath = Storage::disk('documents')->path($document->fichier_path);

        return response()->file($filePath, [
            'Content-Disposition' => 'attachment; filename="' . $document->fichier_original . '"'
        ]);
    }

    /**
     * Envoyer un document (professeur uniquement)
     */
    public function store(StoreDocumentRequest $request)
    {
        $this->authorize('create', Document::class);

        $disk = 'documents';
        $file = $request->file('fichier');
        $path = $file->store('documents/' . now()->format('Y/m'), $disk);

        $document = Document::create([
            'expediteur_id' => $request->user()->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $request->type,
            'fichier_path' => $path,
            'fichier_original' => $file->getClientOriginalName(),
            'taille' => $file->getSize(),
            'filiere_id' => $request->filiere_id,
            'niveau_id' => $request->niveau_id,
            'cours_id' => $request->cours_id,
            'date_expiration' => $request->date_expiration,
        ]);

        // Invalider les caches
        $this->invalidateDocumentCaches($document);

        return response()->json([
            'message' => 'Document envoyé avec succès',
            'data' => new DocumentResource($document)
        ], 201);
    }

    /**
     * Supprimer un document (professeur uniquement)
     */
    public function destroy(Document $document)
    {
        $this->authorize('manage', $document);
        
        // Supprimer le fichier
        if (Storage::disk('documents')->exists($document->fichier_path)) {
            Storage::disk('documents')->delete($document->fichier_path);
        }

        // Invalider les caches
        $this->invalidateDocumentCaches($document);

        $document->delete();

        return response()->json(['message' => 'Document supprimé']);
    }

    // ============= MÉTHODES PRIVÉES =============

    /**
     * Invalider tous les caches liés à un document
     */
    private function invalidateDocumentCaches(Document $document): void
    {
        // Invalider tous les caches de documents (patterns génériques)
        CacheService::forget([
            sprintf('documents:prof:%d:*', $document->expediteur_id),
            'documents:etudiant:*', // Tous les caches étudiants (car le document pourrait concerner plusieurs)
            CacheService::KEYS['stats_dashboard'],
        ]);
    }
}