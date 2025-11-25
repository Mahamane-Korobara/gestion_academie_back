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
        'users' => 'users:all',
        'user' => 'user:%d',
        'roles' => 'roles:all',
        'filieres' => 'filieres:all',
        'filiere' => 'filiere:%d',
        'niveaux' => 'niveaux:filiere:%d',
        'cours' => 'cours:all',
        'cours_niveau' => 'cours:niveau:%d',
        'professeurs' => 'professeurs:all',
        'etudiants' => 'etudiants:all',
        'etudiants_filiere' => 'etudiants:filiere:%d',
        'annee_active' => 'annee:active',
        'semestre_actif' => 'semestre:actif',
        'stats_dashboard' => 'stats:dashboard',
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
}