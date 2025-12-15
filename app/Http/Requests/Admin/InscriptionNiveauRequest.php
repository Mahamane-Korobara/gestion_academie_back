<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\AnneeAcademique;
use App\Models\Semestre;

class InscriptionNiveauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $anneeActive = AnneeAcademique::active()->first();
            $semestreActif = Semestre::active()
                ->where('annee_academique_id', $anneeActive?->id)
                ->first();

            if (!$anneeActive) {
                $validator->errors()->add('system', 'Aucune année académique active.');
            }

            if (!$semestreActif) {
                $validator->errors()->add('system', 'Aucun semestre actif.');
            }
        });
    }
}
