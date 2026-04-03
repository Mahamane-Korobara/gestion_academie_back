<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\RapportsExportService;
use Illuminate\Http\Request;

class RapportAdminController extends Controller
{
    public function __construct(
        private RapportsExportService $exportService
    ) {}

    /**
     * Export des données admin par année académique (Excel ZIP ou SQL)
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'annee_academique_id' => 'required|exists:annees_academiques,id',
            'format' => 'required|in:excel,sql',
            'include' => 'nullable|array',
        ]);

        $result = $this->exportService->export($validated, $request->user());

        return response()->download($result['path'], $result['filename'], [
            'Content-Type' => $result['content_type'],
        ]);
    }
}
