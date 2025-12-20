<?php

namespace App\Services;

use App\Models\Etudiant;
use App\Models\Semestre;
use App\Models\Bulletin;
use App\Models\Note;
use App\Enums\DecisionBulletin;

class CalculAcademique
{
    /**
     * Seuils configurables
     */
    private const SEUIL_REUSSITE = 10.00;
    private const SEUIL_RATTRAPAGE = 8.00;

    /**
     * Calcule et génère le bulletin d'un semestre
     */
    public function calculerMoyenneSemestre(
        Etudiant $etudiant,
        Semestre $semestre,
        ?int $generePar = null
    ): ?Bulletin {
        // 1. Récupérer les inscriptions réelles de l'étudiant
        $inscriptions = $etudiant->inscriptions()
            ->where('semestre_id', $semestre->id)
            ->with(['cours.evaluations.typeEvaluation'])
            ->get();

        if ($inscriptions->isEmpty()) {
            return null;
        }

        // 2. Récupérer toutes les notes validées
        $notesParCours = Note::where('etudiant_id', $etudiant->id)
            ->where('statut', 'validee')
            ->whereHas('evaluation.cours', fn($q) => $q->where('semestre_id', $semestre->id))
            ->with('evaluation.typeEvaluation')
            ->get()
            ->groupBy(fn (Note $note) => $note->evaluation->cours_id);

        $sommePondereeGenerale = 0;
        $sommeCoeffsCours = 0;

        // 3. Calcul par cours
        foreach ($inscriptions as $inscription) {
            $cours = $inscription->cours;
            $notesCours = $notesParCours[$cours->id] ?? collect();
            if ($notesCours->isEmpty()) continue;

            $notesParCode = $notesCours->keyBy(fn (Note $n) => $n->evaluation->typeEvaluation->code);

            $sommePointsCours = 0;
            $sommeCoeffsEvals = 0;

            foreach ($cours->evaluations as $evaluation) {
                $code = $evaluation->typeEvaluation->code;

                // Gestion rattrapage
                if ($code === 'EF' && $notesParCode->has('RATT')) {
                    $noteAUtiliser = $notesParCode['RATT'];
                } elseif ($code === 'RATT') {
                    continue;
                } else {
                    $noteAUtiliser = $notesParCode[$code] ?? null;
                }

                if (!$noteAUtiliser) continue;

                $valeur = $noteAUtiliser->is_absent ? 0 : $noteAUtiliser->note;
                $sommePointsCours += ($valeur * $evaluation->coefficient);
                $sommeCoeffsEvals += $evaluation->coefficient;
            }

            if ($sommeCoeffsEvals === 0) continue;

            $noteFinaleCours = $sommePointsCours / $sommeCoeffsEvals;
            $sommePondereeGenerale += ($noteFinaleCours * $cours->coefficient);
            $sommeCoeffsCours += $cours->coefficient;
        }

        if ($sommeCoeffsCours === 0) return null;

        // 4. Calcul moyenne semestre
        $moyenneSemestre = round($sommePondereeGenerale / $sommeCoeffsCours, 2);

        // 5. Détecter si c'est la dernière année
        $dernierSemestre = Semestre::where('annee_academique_id', $semestre->annee_academique_id)
            ->orderBy('numero', 'desc')
            ->first();

        $isDerniereAnnee = $semestre->id === $dernierSemestre->id;

        $decision = $this->determinerDecision($moyenneSemestre, false, $isDerniereAnnee);

        // 6. Génération / mise à jour bulletin semestriel
        return Bulletin::updateOrCreate(
            [
                'etudiant_id' => $etudiant->id,
                'semestre_id' => $semestre->id,
            ],
            [
                'moyenne_generale' => $moyenneSemestre,
                'decision' => $decision->value,
                'est_genere' => true,
                'date_generation' => now(),
                'genere_par' => $generePar,
            ]
        );
    }

    /**
     * Génération d’un bulletin annuel séparé
     */
    public function genererBulletinAnnuel(
        Etudiant $etudiant,
        int $anneeAcademiqueId,
        ?int $generePar = null
    ): ?Bulletin {
        $semestres = Semestre::where('annee_academique_id', $anneeAcademiqueId)
            ->orderBy('numero')
            ->get();

        if ($semestres->isEmpty()) return null;

        $moyennes = [];

        foreach ($semestres as $semestre) {
            $bulletin = Bulletin::where('etudiant_id', $etudiant->id)
                ->where('semestre_id', $semestre->id)
                ->where('est_genere', true)
                ->first();

            if ($bulletin) {
                $moyennes[] = $bulletin->moyenne_generale;
            }
        }

        if (empty($moyennes)) return null;

        $moyenneAnnuelle = round(array_sum($moyennes) / count($moyennes), 2);

        // Détecter si l’étudiant est en dernière année
        $dernierSemestreCycle = $semestres->last();
        $isDerniereAnnee = true; // ou logique plus avancée selon cycle complet

        $decisionAnnuel = $this->determinerDecision($moyenneAnnuelle, true, $isDerniereAnnee);

        // 1. Créer bulletin annuel séparé
        return Bulletin::updateOrCreate(
            [
                'etudiant_id' => $etudiant->id,
                'semestre_id' => null, // bulletin annuel
            ],
            [
                'moyenne_generale' => $moyenneAnnuelle,
                'decision' => $decisionAnnuel->value,
                'est_genere' => true,
                'date_generation' => now(),
                'genere_par' => $generePar,
            ]
        );
    }

    /**
     * Détermine la décision académique en utilisant l'ENUM
     */
    private function determinerDecision(
        float $moyenne,
        bool $isAnnuel = false,
        bool $isDerniereAnnee = false
    ): DecisionBulletin {
        if ($isAnnuel) {
            if ($moyenne >= self::SEUIL_REUSSITE) {
                return $isDerniereAnnee ? DecisionBulletin::DIPLOME : DecisionBulletin::ADMIS;
            }
            if ($moyenne >= self::SEUIL_RATTRAPAGE) {
                return DecisionBulletin::RATTRAPAGE;
            }
            return DecisionBulletin::REDOUBLANT;
        } else {
            if ($moyenne >= self::SEUIL_REUSSITE) {
                return DecisionBulletin::ADMIS; // session normale peut être gérée différemment si nécessaire
            }
            if ($moyenne >= self::SEUIL_RATTRAPAGE) {
                return DecisionBulletin::RATTRAPAGE;
            }
            return DecisionBulletin::AJOURNE;
        }
    }
}
