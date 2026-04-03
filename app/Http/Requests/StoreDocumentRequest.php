<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Cours;
use Illuminate\Support\Str;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isProfesseur();
    }

    public function rules(): array
    {
        $fileRules = [
            'required',
            'file',
            'max:51200', // 50MB
        ];

        // Scan antivirus (actif même en local)
        if (!config('clamav.skip_validation')) {
            $fileRules[] = 'clamav';
        }

        return [
            'filiere_id' => 'required|exists:filieres,id',
            'niveau_id' => 'required|exists:niveaux,id',
            'cours_id' => 'required|exists:cours,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fichier' => $fileRules,
            'date_expiration' => 'nullable|date|after:today',
        ];
    }

public function withValidator($validator)
{
    $validator->after(function ($validator) {
        $file = $this->file('fichier');

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

        // Validation MIME sécurisée + blacklist renforcée
        if ($file && $file->isValid()) {
            $mimeType = $file->getMimeType();
            $originalName = $file->getClientOriginalName();
            $extension = Str::lower(pathinfo($originalName, PATHINFO_EXTENSION));

            $blockedExtensions = [
                'php','phtml','phar','exe','bat','cmd','com','dll','so','sh','bash','zsh','ps1','vbs','js','jsp','asp','aspx','cgi','pl','rb','py','pyc','jar','msi','scr','htaccess'
            ];

            if ($extension && in_array($extension, $blockedExtensions, true)) {
                $validator->errors()->add('fichier', 'Extension de fichier interdite.');
            }

            // Bloquer les doubles extensions dangereuses (ex: pdf.php, docx.js)
            if (preg_match('/\.(php|phtml|phar|exe|bat|cmd|com|dll|so|sh|bash|zsh|ps1|vbs|js|jsp|asp|aspx|cgi|pl|rb|py|pyc|jar|msi|scr|htaccess)(\.|$)/i', $originalName)) {
                $validator->errors()->add('fichier', 'Nom de fichier interdit (extension dangereuse détectée).');
            }

            // Refus de certains types MIME risqués
            $blockedMimes = [
                'text/html',
                'application/x-php',
                'application/x-httpd-php',
                'application/x-sh',
                'application/x-msdownload',
                'application/x-msdos-program',
            ];
            if (in_array($mimeType, $blockedMimes, true)) {
                $validator->errors()->add('fichier', 'Type MIME de fichier interdit.');
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
