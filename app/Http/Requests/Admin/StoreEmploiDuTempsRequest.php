<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\JourSemaine;
use App\Enums\TypeSeance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreEmploiDuTempsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Règles simplifiées : On ne demande que le strict nécessaire.
     * Le niveau_id et semestre_id sont déjà portés par le cours_id.
     */
    public function rules(): array
    {
        return [
            'cours_id'      => ['required', 'exists:cours,id'],
            'professeur_id' => ['required', 'exists:professeurs,id'],
            'salle_id'      => ['nullable', 'exists:salles,id'],
            'jour'          => ['required', Rule::enum(JourSemaine::class)],
            'type_seance'   => ['required', Rule::enum(TypeSeance::class)],
            'heure_debut'   => ['required', 'date_format:H:i'],
            'heure_fin'     => ['required', 'date_format:H:i', 'after:heure_debut'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cours_id'      => 'cours',
            'professeur_id' => 'professeur',
            'heure_debut'   => 'heure de début',
            'heure_fin'     => 'heure de fin',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Récupération des données du cours pour validation interne
            $cours = DB::table('cours')->where('id', $this->cours_id)->first();
            
            if (!$cours) return;

            // Validation des horaires
            $heureDebut = Carbon::createFromFormat('H:i', $this->heure_debut);
            $heureFin = Carbon::createFromFormat('H:i', $this->heure_fin);

            if ($heureDebut->hour < 8 || $heureFin->hour > 20) {
                $validator->errors()->add('heure_debut', 'Les horaires doivent être compris entre 08:00 et 20:00.');
            }

            $dureeMinutes = $heureDebut->diffInMinutes($heureFin);
            if ($dureeMinutes < 60 || $dureeMinutes > 240) {
                $validator->errors()->add('heure_fin', 'La durée du cours doit être comprise entre 1h et 4h.');
            }

            // Vérifier que le professeur est affecté à ce cours POUR L'ANNÉE DU COURS
            // C'est ici que la logique de "système par année" est verrouillée.
            $profEnseigneCours = DB::table('cours_professeur')
                ->where('professeur_id', $this->professeur_id)
                ->where('cours_id', $this->cours_id)
                ->where('annee_academique_id', $cours->annee_academique_id)
                ->exists();

            if (!$profEnseigneCours) {
                $validator->errors()->add('professeur_id', 'Ce professeur n\'est pas affecté à ce cours pour cette année académique.');
            }
        });
    }
}