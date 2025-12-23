<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TypeDocument;
use App\Models\Cours;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isProfesseur();
    }

    public function rules(): array
    {
        return [
            'filiere_id' => 'required|exists:filieres,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'cours_id' => 'required|exists:cours,id',
            'type' => ['required', Rule::enum(TypeDocument::class)],
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fichier' => 'required|file|max:10240', // 10MB max
            'date_expiration' => 'nullable|date|after:today',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Vérifier que le cours appartient à la filière/niveau
            $cours = Cours::find($this->cours_id);
            if (!$cours || $cours->niveau_id != $this->niveau_id) {
                $validator->errors()->add('cours_id', 'Le cours ne correspond pas au niveau.');
            }

            // Vérifier que le prof enseigne ce cours
            if (!Cours::where('id', $this->cours_id)
                ->whereHas('professeurs', fn($q) => $q->where('professeurs.id', $this->user()->professeur->id))
                ->exists()) {
                $validator->errors()->add('cours_id', 'Vous n\'enseignez pas ce cours.');
            }

            // Vérifier le type MIME
            if ($this->file('fichier')) {
                $mimeType = $this->file('fichier')->getMimeType();
                $allowedMimes = TypeDocument::mimeTypes()[$this->type] ?? [];
                if (!in_array($mimeType, $allowedMimes)) {
                    $validator->errors()->add('fichier', 'Le type de fichier ne correspond pas au type sélectionné.');
                }
            }
        });
    }
}