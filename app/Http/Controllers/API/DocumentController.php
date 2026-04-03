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
use Illuminate\Support\Str;

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
                if ($request->filled('extension')) {
                    $query->where('extension', $request->extension);
                }
                if ($request->filled('mime_type')) {
                    $query->where('mime_type', $request->mime_type);
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
                if ($request->filled('extension')) {
                    $query->where('extension', $request->extension);
                }
                if ($request->filled('mime_type')) {
                    $query->where('mime_type', $request->mime_type);
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
    public function downloadByUuid(string $uuid, Request $request)
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();

        if ($request->user()->isEtudiant()) {
            $this->authorize('view', $document);
        } else {
            $this->authorize('viewProfesseur', $document);
        }

        return Storage::disk('documents')->download(
            $document->fichier_path,
            $document->fichier_original,
            [
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Preview texte sécurisé
     */
    public function previewByUuid(string $uuid, Request $request)
    {
        $document = Document::where('uuid', $uuid)->firstOrFail();

        if ($request->user()->isEtudiant()) {
            $this->authorize('view', $document);
        } else {
            $this->authorize('viewProfesseur', $document);
        }

        if (!$document->isTextPreviewable()) {
            return response()->json(['message' => 'Prévisualisation non disponible.'], 422);
        }

        $path = $document->fichier_path;
        if (!Storage::disk('documents')->exists($path)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        // Limiter la taille de preview à 1 MB
        $maxBytes = 1024 * 1024;
        $content = Storage::disk('documents')->get($path);
        if (strlen($content) > $maxBytes) {
            $content = substr($content, 0, $maxBytes);
        }

        return response($content, 200, [
            'Content-Type' => $document->mime_type ?: 'text/plain',
            'X-Content-Type-Options' => 'nosniff',
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
        $uuid = (string) Str::uuid();
        $extension = Str::lower($file->getClientOriginalExtension() ?: '');
        $folder = now()->format('Y/m');
        $filename = $uuid . ($extension ? '.' . $extension : '');
        $path = $file->storeAs($folder, $filename, $disk);

        $document = Document::create([
            'uuid' => $uuid,
            'expediteur_id' => $request->user()->id,
            'titre' => $request->titre,
            'description' => $request->description,
            'type' => $this->determineCategory($extension, $file->getMimeType()),
            'fichier_path' => $path,
            'fichier_original' => $file->getClientOriginalName(),
            'extension' => $extension,
            'mime_type' => $file->getMimeType(),
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

    private function determineCategory(string $extension, ?string $mimeType): string
    {
        $ext = Str::lower($extension);
        $mime = Str::lower($mimeType ?? '');

        $categories = [
            'docs' => ['pdf','doc','docx','odt','rtf','txt'],
            'data' => ['xls','xlsx','csv','tsv','ods','parquet','sav','dta','sas7bdat','rds','rdata'],
            'code' => ['py','r','ipynb','do','tex','sql','js','ts','java','c','cpp','cs','go','php','rb','sh'],
            'slides' => ['ppt','pptx','odp'],
            'images' => ['jpg','jpeg','png','gif','webp','svg'],
            'archives' => ['zip','rar','7z','tar','gz','bz2','xz'],
            'audio' => ['mp3','wav','ogg','flac','aac'],
            'video' => ['mp4','mkv','mov','avi','webm'],
        ];

        foreach ($categories as $category => $extensions) {
            if (in_array($ext, $extensions, true)) {
                return $category;
            }
        }

        if (str_starts_with($mime, 'image/')) return 'images';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        if (str_starts_with($mime, 'video/')) return 'video';
        if (str_starts_with($mime, 'text/')) return 'docs';

        return 'autres';
    }
}
