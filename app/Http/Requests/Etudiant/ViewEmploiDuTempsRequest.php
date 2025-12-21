<?php

namespace App\Http\Requests\Etudiant;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\JourSemaine;
use Illuminate\Validation\Rule;

class ViewEmploiDuTempsRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Vérifier que l'utilisateur est un étudiant avec un niveau assigné
        return $this->user()->isEtudiant();
    }

    public function rules(): array
    {
        return [
            'jour' => ['nullable', Rule::enum(JourSemaine::class)],
            'semestre_id' => ['nullable', 'exists:semestres,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'jour.enum' => 'Le jour doit être valide (Lundi, Mardi, etc.)',
            'semestre_id.exists' => 'Le semestre sélectionné n\'existe pas',
        ];
    }
}