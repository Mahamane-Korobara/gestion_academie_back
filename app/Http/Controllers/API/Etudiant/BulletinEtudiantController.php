<?php

namespace App\Http\Controllers\API\Etudiant;

use App\Http\Controllers\Controller;
use App\Models\Bulletin;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BulletinEtudiantController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private PdfService $pdfService
    ) {}

    /**
     * Télécharger le bulletin en PDF
     */
    public function telechargerPDF(Request $request, int $bulletinId)
    {
        $bulletin = Bulletin::with([
            'etudiant.niveau.filiere',
            'semestre',
            'etudiant.inscriptions.cours.evaluations.typeEvaluation'
        ])->findOrFail($bulletinId);

        // Utilisation de la Policy
        $this->authorize('telechargerBulletin', $bulletin);

        return $this->pdfService->telechargerBulletinPDF($bulletin);
    }
}