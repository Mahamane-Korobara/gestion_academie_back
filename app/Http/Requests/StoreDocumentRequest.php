<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\TypeDocument;
use App\Models\Cours;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

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
            'fichier' => 'required|file|max:40960', // 10MB max
            'date_expiration' => 'nullable|date|after:today',
        ];
    }

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $file = $this->file('fichier');

        // DEBUG - À SUPPRIMER APRÈS
        if ($this->hasFile('fichier')) {
            Log::info('Fichier détecté:', [
                'size' => $file ? $file->getSize() : 'NULL',
                'isValid' => $file ? $file->isValid() : 'NULL',
                'error' => $file ? $file->getError() : 'NULL',
                'errorMessage' => $file ? $file->getErrorMessage() : 'NULL'
            ]);
        } else {
            Log::info('Aucun fichier détecté dans la requête');
        }

        // Validation du cours et du niveau
        $cours = Cours::find($this->cours_id);
        if (!$cours || $cours->niveau_id != $this->niveau_id) {
            $validator->errors()->add('cours_id', 'Le cours ne correspond pas au niveau.');
        }

        // Validation du professeur
        if ($this->user()->professeur && !Cours::where('id', $this->cours_id)
            ->whereHas('professeurs', fn($q) => $q->where('professeurs.id', $this->user()->professeur->id))
            ->exists()) {
            $validator->errors()->add('cours_id', 'Vous n\'enseignez pas ce cours.');
        }

        // Validation MIME sécurisée
        if ($file && $file->isValid()) {
            $mimeType = $file->getMimeType();
            $allowedMimes = TypeDocument::mimeTypes()[$this->type->value ?? $this->type] ?? [];
            if (!in_array($mimeType, $allowedMimes)) {
                $validator->errors()->add('fichier', 'Le type de fichier ne correspond pas au type sélectionné.');
            }
        } elseif ($this->isMethod('POST')) {
            // Ajouter plus de détails sur l'erreur
            $errorCode = $file ? $file->getError() : 'NO_FILE';
            $errorMessage = match($errorCode) {
                UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse upload_max_filesize dans php.ini',
                UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse MAX_FILE_SIZE dans le formulaire HTML',
                UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
                UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement',
                default => "Le fichier est invalide (code: $errorCode)"
            };
            
            $validator->errors()->add('fichier', $errorMessage);
        }
    });
}
}