<?php

namespace App\Observers;

use App\Models\Note;
use App\Services\CacheService;

class NoteObserver
{
    /**
     * Se déclenche après chaque mise à jour (validation, changement de note, etc.)
     */
    public function updated(Note $note): void
    {
        $this->clearEtudiantCache($note->etudiant_id);
    }

    /**
     * Se déclenche si une note est créée (ex: saisie initiale)
     */
    public function created(Note $note): void
    {
        $this->clearEtudiantCache($note->etudiant_id);
    }

    /**
     * Se déclenche si une note est supprimée
     */
    public function deleted(Note $note): void
    {
        $this->clearEtudiantCache($note->etudiant_id);
    }

    /**
     * Logique de nettoyage centralisée
     */
    private function clearEtudiantCache(int $etudiantId): void
    {
        // On nettoie le dashboard
        CacheService::forget("etudiant:dashboard:{$etudiantId}");
        
        // On nettoie toutes les pages de la liste des notes de cet étudiant
        // On utilise un pattern car on ne connaît pas le numéro de page consulté
        CacheService::forget("etudiant:{$etudiantId}:notes:page:*");    }
}
