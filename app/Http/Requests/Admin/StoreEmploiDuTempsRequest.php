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
    /**
     * Détermine si l'utilisateur est autorisé à faire cette requête
     */
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée par le Controller/Policy
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'cours_id' => ['required', 'exists:cours,id'],
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'professeur_id' => ['required', 'exists:professeurs,id'],
            'semestre_id' => ['required', 'exists:semestres,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
            
            // Validation des Enums
            'jour' => ['required', Rule::enum(JourSemaine::class)],
            'type_seance' => ['required', Rule::enum(TypeSeance::class)],

            // Validation des heures
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cours_id' => 'cours',
            'niveau_id' => 'niveau',
            'professeur_id' => 'professeur',
            'heure_debut' => 'heure de début',
            'heure_fin' => 'heure de fin',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            // érifier que les horaires sont dans une plage réaliste (08:00 - 20:00)
            $heureDebut = Carbon::createFromFormat('H:i', $this->heure_debut);
            $heureFin = Carbon::createFromFormat('H:i', $this->heure_fin);

            if ($heureDebut->hour < 8 || $heureFin->hour > 20) {
                $validator->errors()->add('heure_debut', 'Les cours doivent être entre 08:00 et 20:00');
            }

            // Vérifier durée minimale (ex: 1h minimum)
            $dureeMinutes = $heureDebut->diffInMinutes($heureFin);
            if ($dureeMinutes < 60) {
                $validator->errors()->add('heure_fin', 'Un cours doit durer au moins 1 heure');
            }

            // Vérifier durée maximale (ex: 4h maximum)
            if ($dureeMinutes > 240) {
                $validator->errors()->add('heure_fin', 'Un cours ne peut pas dépasser 4 heures');
            }
            // Vérifier que le professeur enseigne ce cours
            $profEnseigneCours = DB::table('cours_professeur')
                ->where('professeur_id', $this->professeur_id)
                ->where('cours_id', $this->cours_id)
                ->exists();

            if (!$profEnseigneCours) {
                $validator->errors()->add('professeur_id', 'Ce professeur n\'enseigne pas ce cours');
            }

            // Vérifier que le cours appartient bien au niveau
            $coursAppartientNiveau = DB::table('cours')
                ->where('id', $this->cours_id)
                ->where('niveau_id', $this->niveau_id)
                ->exists();

            if (!$coursAppartientNiveau) {
                $validator->errors()->add('niveau_id', 'Ce cours n\'appartient pas à ce niveau');
            }

            // Vérifier que le cours est dans le bon semestre
            $coursAppartientSemestre = DB::table('cours')
                ->where('id', $this->cours_id)
                ->where('semestre_id', $this->semestre_id)
                ->exists();

            if (!$coursAppartientSemestre) {
                $validator->errors()->add('semestre_id', 'Ce cours n\'appartient pas à ce semestre');
            }
        });
    }
}