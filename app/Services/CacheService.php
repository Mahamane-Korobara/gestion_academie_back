<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    // Durée de cache par défaut
    const DEFAULT_TTL = 3600; // 1 heure
    const SHORT_TTL = 300;    // 5 minutes
    const LONG_TTL = 86400;   // 24 heures

    /**
     * Clés de cache
     */
    const KEYS = [
        // Utilisateurs et rôles
        'users' => 'users:all',
        'user' => 'user:%d',
        'roles' => 'roles:all',
        
        // Filières et niveaux
        'filieres' => 'filieres:all',
        'filiere' => 'filiere:%d',
        'niveaux' => 'niveaux:filiere:%d',
        
        // Cours
        'cours' => 'cours:all',
        'cours_niveau' => 'cours:niveau:%d',
        
        // Professeurs et étudiants
        'professeurs' => 'professeurs:all',
        'etudiants' => 'etudiants:all',
        'etudiants_filiere' => 'etudiants:filiere:%d',
        
        // Années et semestres
        'annee_active' => 'annee:active',
        'semestre_actif' => 'semestre:actif',
        
        // Dashboard
        'stats_dashboard' => 'stats:dashboard',
        
        // Bulletins
        'bulletin_semestre' => 'bulletin:etudiant:%d:semestre:%d',
        'bulletin_annuel' => 'bulletin:etudiant:%d:annuel:%d',
        'bulletins_list' => 'bulletins:all:page:%d',
        
        // Notes
        'notes_en_attente' => 'notes:en_attente:page:%d:filters:%s',
        
        // Planning Professeur
        'prof_planning' => 'prof:%d:planning:sem:%d:niv:%s:jour:%s',
        'prof_planning_week' => 'prof:%d:planning:week:sem:%d:niv:%s',
        'prof_niveaux' => 'prof:%d:niveaux:sem:%d',
        
        // Planning Étudiant
        'etudiant_planning' => 'etudiant:%d:planning:niv:%d:sem:%d:jour:%s',
        'etudiant_planning_week' => 'etudiant:%d:planning:week:niv:%d:sem:%d',
        'etudiant_planning_jour' => 'etudiant:%d:jour:%s:niv:%d:sem:%d',
        'etudiant_resume' => 'etudiant:%d:resume:niv:%d:sem:%d',
        'etudiant_prochains' => 'etudiant:%d:prochains:niv:%d:sem:%d:%s:%s',
        
        // Planning Niveau
        'niveau_planning' => 'niveau:%d:planning:sem:%d',
        'niveau_planning_jour' => 'niveau:%d:planning:sem:%d:jour:%s',
        
        // Planning Salle
        'salle_planning' => 'salle:%d:planning:sem:%d',
        
        // Helpers Admin
        'profs_disponibles' => 'profs_disponibles_niv_%d_sem_%d_%s_%s_%s',
        'cours_disponibles' => 'cours_disponibles_prof_%d_niv_%d_sem_%d',

        // Annonces
        'annonces_global' => 'annonces:global:page:%d',
        'annonces_filtre' => 'annonces:filtre:%s:%d:page:%d', // type:id:page
        'annonce_detail'  => 'annonce:%d',

        // Professeur - Cours
        'prof_cours_list' => 'prof:%d:cours:list',

        // Documents
        'documents_prof' => 'documents:prof:%d:filiere:%d:niveau:%d:cours:%d:page:%d',
        'documents_etudiant' => 'documents:etudiant:%d:filiere:%d:niveau:%d:cours:%d:page:%d',

        // Messages Utilisateur
        'user_unread_messages' => 'user:%d:messages:unread',
    ];

    /**
     * Obtenir une clé de cache formatée
     */
    public static function key(string $key, ...$params): string
    {
        $template = self::KEYS[$key] ?? $key;
        return sprintf($template, ...$params);
    }

    /**
     * Invalider le cache par pattern
     */
    public static function forget(string|array $keys): void
    {
        $keys = is_array($keys) ? $keys : [$keys];
        
        foreach ($keys as $key) {
            if (str_contains($key, '*')) {
                // Si le pattern contient *, on flush tous les caches correspondants
                self::forgetPattern($key);
            } else {
                Cache::forget($key);
            }
        }
    }

    /**
     * Invalider par pattern (ex: "users:*")
     */
    private static function forgetPattern(string $pattern): void
    {
        // Pour Redis/Memcached, on peut utiliser des patterns
        // Pour file cache, on flush tout
        if (config('cache.default') === 'redis') {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            // Fallback : on flush les caches principaux
            Cache::flush();
        }
    }

    // ============================================================
    // MÉTHODES D'INVALIDATION PAR ENTITÉ
    // ============================================================

    /**
     * Invalider tous les caches liés aux utilisateurs
     */
    public static function forgetUsers(): void
    {
        self::forget([
            self::KEYS['users'],
            'user:*',
            self::KEYS['stats_dashboard'],
        ]);
    }

    /**
     * Invalider tous les caches liés aux filières
     */
    public static function forgetFilieres(): void
    {
        self::forget([
            self::KEYS['filieres'],
            'filiere:*',
            'niveaux:*',
            self::KEYS['stats_dashboard'],
        ]);
    }

    /**
     * Invalider tous les caches liés aux cours
     */
    public static function forgetCours(): void
    {
        self::forget([
            self::KEYS['cours'],
            'cours:*',
            self::KEYS['stats_dashboard'],
        ]);
    }

    /**
     * Invalider tous les caches liés aux bulletins
     */
    public static function forgetBulletins(int $etudiantId): void
    {
        self::forget([
            sprintf('bulletin:etudiant:%d:*', $etudiantId),
            'bulletins:all:*',
            self::KEYS['stats_dashboard'],
        ]);
    }

    // ============================================================
    // MÉTHODES D'INVALIDATION EMPLOI DU TEMPS
    // ============================================================

    /**
     * Invalider tous les caches du planning d'un professeur
     */
    public static function forgetProfPlanning(int $profId): void
    {
        self::forget(sprintf('prof:%d:*', $profId));
    }

    /**
     * Invalider tous les caches du planning d'un étudiant spécifique
     */
    public static function forgetEtudiantPlanning(int $etudiantId): void
    {
        self::forget(sprintf('etudiant:%d:*', $etudiantId));
    }

    /**
     * Invalider tous les caches du planning d'un niveau
     * (utilisé quand on crée/modifie/supprime un emploi du temps)
     * 
     * @param int $niveauId
     * @param int|null $semestreId Si spécifié, invalide seulement pour ce semestre
     */
    public static function forgetNiveauPlanning(int $niveauId, ?int $semestreId = null): void
    {
        if ($semestreId) {
            // Invalidation ciblée pour un semestre spécifique
            self::forget([
                sprintf('niveau:%d:planning:sem:%d*', $niveauId, $semestreId),
                sprintf('etudiant:*:planning:niv:%d:sem:%d*', $niveauId, $semestreId),
                sprintf('etudiant:*:planning:week:niv:%d:sem:%d', $niveauId, $semestreId),
                sprintf('etudiant:*:jour:*:niv:%d:sem:%d', $niveauId, $semestreId),
                sprintf('etudiant:*:resume:niv:%d:sem:%d', $niveauId, $semestreId),
                sprintf('etudiant:*:prochains:niv:%d:sem:%d:*', $niveauId, $semestreId),
            ]);
        } else {
            // Invalidation globale pour tous les semestres
            self::forget([
                sprintf('niveau:%d:*', $niveauId),
                sprintf('etudiant:*:planning:niv:%d:*', $niveauId),
                sprintf('etudiant:*:planning:week:niv:%d:*', $niveauId),
                sprintf('etudiant:*:jour:*:niv:%d:*', $niveauId),
                sprintf('etudiant:*:resume:niv:%d:*', $niveauId),
                sprintf('etudiant:*:prochains:niv:%d:*', $niveauId),
            ]);
        }
    }

    /**
     * Invalider le cache d'une salle
     */
    public static function forgetSallePlanning(int $salleId, ?int $semestreId = null): void
    {
        if ($semestreId) {
            self::forget(sprintf('salle:%d:planning:sem:%d', $salleId, $semestreId));
        } else {
            self::forget(sprintf('salle:%d:*', $salleId));
        }
    }

    /**
     * Invalider tous les caches liés à un emploi du temps
     * (professeur, niveau, étudiants du niveau, salle)
     * 
     * Utilisé dans les contrôleurs Admin lors de la création/modification/suppression
     */
    public static function forgetEmploiDuTemps(int $profId, int $niveauId, int $semestreId, ?int $salleId = null): void
    {
        // Invalider le cache du professeur
        self::forgetProfPlanning($profId);
        
        // Invalider le cache du niveau (et tous les étudiants de ce niveau)
        self::forgetNiveauPlanning($niveauId, $semestreId);

        // Invalider le cache de la salle si présente
        if ($salleId) {
            self::forgetSallePlanning($salleId, $semestreId);
        }

        // Invalider les caches des helpers admin
        self::forgetHelpersCaches($niveauId, $semestreId, $profId);

        // Invalider le dashboard
        self::forget(self::KEYS['stats_dashboard']);
    }

    /**
     * Invalider les caches des helpers admin (profs disponibles, cours disponibles)
     */
    public static function forgetHelpersCaches(int $niveauId, int $semestreId, ?int $profId = null): void
    {
        // Invalider tous les profs disponibles pour ce niveau/semestre
        self::forget(sprintf('profs_disponibles_niv_%d_sem_%d_*', $niveauId, $semestreId));

        // Si on a un prof, invalider ses cours disponibles
        if ($profId) {
            self::forget(sprintf('cours_disponibles_prof_%d_niv_%d_sem_%d', $profId, $niveauId, $semestreId));
        }
    }

    /**
     * Invalider tous les caches liés à un semestre
     * Utile lors du changement de semestre actif
     */
    public static function forgetSemestre(int $semestreId): void
    {
        self::forget([
            self::KEYS['semestre_actif'],
            sprintf('*:sem:%d*', $semestreId),
            self::KEYS['stats_dashboard'],
        ]);
    }

    /**
     * Invalider tout le cache des emplois du temps
     */
    public static function forgetAllEmploisDuTemps(): void
    {
        self::forget([
            'prof:*',
            'etudiant:*:planning*',
            'etudiant:*:jour:*',
            'etudiant:*:resume:*',
            'etudiant:*:prochains:*',
            'niveau:*:planning*',
            'salle:*:planning*',
            'profs_disponibles_*',
            'cours_disponibles_*',
            self::KEYS['stats_dashboard'],
        ]);
    }

    public static function forgetAnnonces(int $annonceId): void
    {
        self::forget([
            // Le détail de l'annonce spécifique
            sprintf('annonce:%d', $annonceId),
            
            // Toutes les listes
            'annonces:*',
            
            // Le dashboard qui compte les annonces
            self::KEYS['stats_dashboard'],
        ]);
    }

    /**
     * Invalider la liste des cours d'un prof
     */
    public static function forgetProfCours(int $profId): void
    {
        self::forget(sprintf('prof:%d:cours:list', $profId));
    }

    public static function forgetUserMessages(int $userId): void
    {
        self::forget(sprintf('user:%d:messages:unread', $userId));
    }
    // ============================================================
    // MÉTHODES UTILITAIRES
    // ============================================================

    /**
     * Vérifier si une clé existe dans le cache
     */
    public static function has(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Récupérer une valeur du cache
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Mettre une valeur en cache
     */
    public static function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Mettre une valeur en cache si elle n'existe pas déjà
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
    }

    /**
     * Vider tout le cache
     */
    public static function flush(): bool
    {
        return Cache::flush();
    }

    /**
     * Obtenir des statistiques sur le cache
     */
    public static function getStats(): array
    {
        if (config('cache.default') === 'redis') {
            try {
                $redis = Cache::getRedis();
                $info = $redis->info();
                
                return [
                    'driver' => 'redis',
                    'keys_count' => $redis->dbSize(),
                    'memory_used' => $info['used_memory_human'] ?? 'N/A',
                    'hits' => $info['keyspace_hits'] ?? 0,
                    'misses' => $info['keyspace_misses'] ?? 0,
                ];
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }

        return [
            'driver' => config('cache.default'),
            'message' => 'Stats not available for this driver',
        ];
    }
}