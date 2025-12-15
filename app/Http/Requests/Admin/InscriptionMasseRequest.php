<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Semestre;
use App\Models\AnneeAcademique;

class InscriptionMasseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'niveau_id' => ['required', 'exists:niveaux,id'],
            'filiere_id' => ['required', 'exists:filieres,id'],
            'semestre_id' => ['required', 'exists:semestres,id'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            if (!$this->filled('semestre_id')) {
                return;
            }

            $semestre = Semestre::find($this->semestre_id);
            if (!$semestre) {
                return;
            }

            $anneeActive = AnneeAcademique::active()->first();

            if (!$anneeActive || $semestre->annee_academique_id !== $anneeActive->id) {
                $validator->errors()->add(
                    'semestre_id',
                    'Le semestre doit appartenir à l’année académique active.'
                );
            }
        });
    }

}