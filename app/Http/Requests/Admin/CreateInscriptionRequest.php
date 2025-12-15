<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Etudiant;
use App\Models\Semestre;
use App\Models\AnneeAcademique;
use App\Models\Cours;
use App\Models\Inscription;

class CreateInscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'etudiant_id' => ['required', 'exists:etudiants,id'],
            'cours_ids' => ['required', 'array', 'min:1'],
            'cours_ids.*' => ['exists:cours,id'],
            'semestre_id' => ['required', 'exists:semestres,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->hasValidData()) {
                return;
            }

            $etudiant = Etudiant::find($this->etudiant_id);
            $semestre = Semestre::find($this->semestre_id);

            // Année académique active
            $anneeActive = AnneeAcademique::active()->first();
            if (!$anneeActive || $semestre->annee_academique_id !== $anneeActive->id) {
                $validator->errors()->add(
                    'semestre_id',
                    'Le semestre doit appartenir à l’année académique active.'
                );
            }

            // Vérifier les cours
            $cours = Cours::whereIn('id', $this->cours_ids)->get();

            foreach ($cours as $c) {
                if (
                    $c->niveau_id !== $etudiant->niveau_id ||
                    $c->semestre_id !== $semestre->id
                ) {
                    $validator->errors()->add(
                        'cours_ids',
                        "Le cours {$c->id} n'appartient pas au niveau ou semestre de l'étudiant."
                    );
                    break;
                }
            }

            // Vérifier doublons
            $exists = Inscription::where('etudiant_id', $this->etudiant_id)
                ->whereIn('cours_id', $this->cours_ids)
                ->where('semestre_id', $this->semestre_id)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'cours_ids',
                    'L’étudiant est déjà inscrit à l’un de ces cours pour ce semestre.'
                );
            }
        });
    }

    private function hasValidData(): bool
    {
        return $this->filled('etudiant_id')
            && $this->filled('semestre_id')
            && is_array($this->cours_ids);
    }
}
