<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Cours;
use App\Models\Semestre;
use Carbon\Carbon;

class CreateEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'type_evaluation_id' => ['required', 'exists:types_evaluations,id'],
            'semestre_id' => ['required', 'exists:semestres,id'],
            'titre' => ['required', 'string', 'max:255'],
            'coefficient' => ['required', 'numeric', 'min:0.1', 'max:10.0'],
            'date_evaluation' => ['required', 'date'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'heure_fin' => ['nullable', 'date_format:H:i', 'after:heure_debut'],
            'salle_id' => ['nullable', 'exists:salles,id'],
            'instructions' => ['nullable', 'string'],
            'statut' => ['sometimes', Rule::in(['planifiee', 'en_cours', 'terminee', 'annulee'])],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasValidData()) {
                // Vérifier que le semestre correspond à l'année du cours
                $cours = Cours::find($this->cours_id);
                if ($cours && $cours->semestre_id !== $this->semestre_id) {
                    $validator->errors()->add('semestre_id', 'Le semestre ne correspond pas à celui du cours.');
                }

                // Vérifier que la date est dans la période du semestre
                $semestre = Semestre::find($this->semestre_id);
                $dateEval = Carbon::parse($this->date_evaluation);
                if ($dateEval->lt($semestre->date_debut) || $dateEval->gt($semestre->date_fin)) {
                    $validator->errors()->add('date_evaluation', 'La date doit être dans la période du semestre.');
                }

            }
        });
    }

    private function hasValidData(): bool
    {
        return $this->cours_id && $this->semestre_id && $this->date_evaluation;
    }
}