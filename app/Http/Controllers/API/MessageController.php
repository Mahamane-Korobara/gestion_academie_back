<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\ReplyMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use App\Models\Cours;
use App\Models\Etudiant;
use App\Http\Requests\Professeur\StoreMessageMasseRequest;
use App\Services\LogService;
use App\Services\CacheService;
use App\Enums\ActionLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MessageController extends Controller
{
    use AuthorizesRequests;

    /**
     * Lister la boîte de réception (messages reçus)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Messages reçus (non supprimés)
        $query = Message::with('expediteur')
            ->where('destinataire_id', $user->id)
            ->whereNull('deleted_at_destinataire')
            ->withCount('reponses');

        // Filtres
        if ($request->filled('is_lu')) {
            $query->where('is_lu', $request->is_lu);
        }
        if ($request->filled('expediteur_id')) {
            $query->where('expediteur_id', $request->expediteur_id);
        }

        // Charger les réponses
        if ($request->boolean('with_reponses')) {
            $query->with('reponses.expediteur');
        }

        return MessageResource::collection(
            $query->orderByDesc('created_at')->paginate(20)
        );
    }

    /**
     * Messages envoyés par l'utilisateur
     */
    public function sent(Request $request)
    {
        $user = $request->user();

        $query = Message::with('destinataire')
            ->where('expediteur_id', $user->id)
            ->whereNull('deleted_at_expediteur')
            ->withCount('reponses');

        return MessageResource::collection(
            $query->orderByDesc('created_at')->paginate(20)
        );
    }

    /**
     * Voir un message spécifique (et ses réponses)
     */
    public function show(Message $message, Request $request): JsonResponse
    {
        $this->authorize('view', $message);

        // Marquer comme lu si destinataire
        if ($message->destinataire_id === $request->user()->id && !$message->is_lu) {
            $message->update([
                'is_lu' => true,
                'date_lecture' => now()
            ]);

            // Invalider le cache du compteur de messages non lus
            $this->invalidateUnreadCache($request->user()->id);
        }

        return response()->json([
            'data' => new MessageResource($message->load('reponses.expediteur', 'expediteur', 'destinataire'))
        ]);
    }

    /**
     * Envoyer un nouveau message
     */
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $this->authorize('create', Message::class);

        $data = $request->validated();
        $data['expediteur_id'] = $request->user()->id;

        $message = Message::create($data);

        // Invalider le cache du destinataire
        $this->invalidateUnreadCache($message->destinataire_id);

        // Log
        LogService::write(
            ActionLog::CREATE,
            "Envoi d'un message à {$message->destinataire->name}",
            $message,
            null,
            ['sujet' => $message->sujet]
        );

        return response()->json([
            'message' => 'Message envoyé avec succès',
            'data' => new MessageResource($message->load('expediteur', 'destinataire'))
        ], 201);
    }

    /**
     * Répondre à un message
     */
    public function reply(ReplyMessageRequest $request, $id)
    {
        $messageParent = Message::findOrFail($id);
        $user = $request->user();
        
        $this->authorize('view', $messageParent);
        
        $data = [
            'expediteur_id' => $user->id,
            'destinataire_id' => $messageParent->expediteur_id,
            'message_parent_id' => $messageParent->id,
            'sujet' => $request->input('sujet', 'RE: ' . $messageParent->sujet),
            'contenu' => $request->input('contenu'),
            'is_lu' => false,
        ];
        
        $reponse = Message::create($data);
        
        return new MessageResource($reponse->load('expediteur'));
    }

    public function storeMasse(StoreMessageMasseRequest $request): JsonResponse
    {
        
        // Vérifier que le professeur enseigne ce cours
        $cours = Cours::where('id', $request->cours_id)
            ->where('niveau_id', $request->niveau_id)
            ->whereHas('professeurs', function ($q) use ($request) {
                $q->where('professeurs.id', $request->user()->professeur->id);
            })
            ->firstOrFail();

        // Récupérer tous les étudiants inscrits à ce cours
        $etudiants = Etudiant::whereHas('inscriptions', function ($q) use ($request) {
                $q->where('cours_id', $request->cours_id);
            })
            ->where('filiere_id', $request->filiere_id)
            ->where('niveau_id', $request->niveau_id)
            ->get();

        if ($etudiants->isEmpty()) {
            return response()->json([
                'message' => 'Aucun étudiant trouvé pour ces critères.'
            ], 404);
        }

        // Créer un message pour chaque étudiant
        $messages = [];
        foreach ($etudiants as $etudiant) {
            $message = Message::create([
                'expediteur_id' => $request->user()->id,
                'destinataire_id' => $etudiant->user_id,
                'sujet' => $request->sujet,
                'contenu' => $request->contenu,
                'is_lu' => false,
            ]);
            
            // Invalider le cache de l'étudiant
            $this->invalidateUnreadCache($etudiant->user_id);
            $messages[] = $message;
        }

        // Log
        LogService::write(
            ActionLog::CREATE,
            "Envoi de messages en masse à {$etudiants->count()} étudiants",
            null,
            null,
            [
                'filiere_id' => $request->filiere_id,
                'niveau_id' => $request->niveau_id,
                'cours_id' => $request->cours_id,
                'sujet' => $request->sujet,
                'nombre_etudiants' => $etudiants->count()
            ]
        );

        return response()->json([
            'message' => "Message envoyé à {$etudiants->count()} étudiants",
            'destinataires' => $etudiants->pluck('matricule')->toArray()
        ], 201);
    }

    /**
     * Supprimer un message (soft delete côté expéditeur/destinataire)
     */
    public function destroy(Message $message, Request $request): JsonResponse
    {
        $this->authorize('delete', $message);

        // Soft delete côté expéditeur
        if ($message->expediteur_id === $request->user()->id) {
            $message->update(['deleted_at_expediteur' => now()]);
        }
        // Soft delete côté destinataire
        elseif ($message->destinataire_id === $request->user()->id) {
            $message->update(['deleted_at_destinataire' => now()]);
            
            // Si le message n'était pas lu, invalider le cache
            if (!$message->is_lu) {
                $this->invalidateUnreadCache($request->user()->id);
            }
        }

        return response()->json([
            'message' => 'Message supprimé avec succès'
        ]);
    }

    /**
     * Marquer comme lu
     */
    public function markAsRead(Message $message, Request $request): JsonResponse
    {
        $this->authorize('markAsRead', $message);

        if (!$message->is_lu) {
            $message->update([
                'is_lu' => true,
                'date_lecture' => now()
            ]);

            // Invalider le cache
            $this->invalidateUnreadCache($request->user()->id);
        }

        return response()->json([
            'message' => 'Message marqué comme lu',
            'is_lu' => true
        ]);
    }

    /**
     * Marquer plusieurs messages comme lus
     */
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id'
        ]);

        $updated = Message::whereIn('id', $request->message_ids)
            ->where('destinataire_id', $request->user()->id)
            ->where('is_lu', false)
            ->update([
                'is_lu' => true,
                'date_lecture' => now()
            ]);

        if ($updated > 0) {
            $this->invalidateUnreadCache($request->user()->id);
        }

        return response()->json([
            'message' => "$updated message(s) marqué(s) comme lu(s)",
            'updated_count' => $updated
        ]);
    }

    /**
     * Compter les messages non lus
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cacheKey = "messages_unread_count_user_{$userId}";

        $count = CacheService::remember($cacheKey, function () use ($userId) {
            return Message::where('destinataire_id', $userId)
                ->where('is_lu', false)
                ->whereNull('deleted_at_destinataire')
                ->count();
        }, CacheService::SHORT_TTL);

        return response()->json([
            'unread_count' => $count
        ]);
    }

    /**
     * Statistiques de messagerie
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = [
            'total_recus' => Message::where('destinataire_id', $userId)
                ->whereNull('deleted_at_destinataire')
                ->count(),
            
            'total_envoyes' => Message::where('expediteur_id', $userId)
                ->whereNull('deleted_at_expediteur')
                ->count(),
            
            'non_lus' => Message::where('destinataire_id', $userId)
                ->where('is_lu', false)
                ->whereNull('deleted_at_destinataire')
                ->count(),
            
            'conversations_actives' => Message::where(function($query) use ($userId) {
                    $query->where('destinataire_id', $userId)
                          ->orWhere('expediteur_id', $userId);
                })
                ->whereNull('message_parent_id')
                ->distinct()
                ->count(),
        ];

        return response()->json($stats);
    }


    /**
     * Invalider le cache du compteur de messages non lus
     */
    private function invalidateUnreadCache(int $userId): void
    {
        $cacheKey = "messages_unread_count_user_{$userId}";
        CacheService::forget($cacheKey);
    }
}