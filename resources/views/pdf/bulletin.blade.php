<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin Académique</title>
    <style>
        @page { margin: 2cm; size: A4; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 21cm; margin: 0 auto; padding: 0 1.5cm; }
        .header { text-align: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 3px solid #2c3e50; }
        .university-name { font-size: 22px; font-weight: bold; color: #2c3e50; margin-bottom: 8px; }
        .faculty-name { font-size: 18px; color: #3498db; margin-bottom: 5px; }
        .document-title { font-size: 24px; font-weight: bold; color: #2c3e50; margin: 20px 0 15px; text-align: center; text-transform: uppercase; }
        .student-info { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3498db; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .info-label { font-weight: bold; color: #2c3e50; width: 40%; }
        .info-value { width: 58%; text-align: right; }
        
        /* Tableau des notes */
        .notes-section { margin: 25px 0; }
        .section-title { font-size: 18px; font-weight: bold; margin-bottom: 15px; color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        .course-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .course-table th { background: #3498db; color: white; padding: 10px; text-align: center; }
        .course-table td { padding: 8px; text-align: center; border: 1px solid #ddd; }
        .course-header { background: #eaf2f8; font-weight: bold; }
        .evaluation-row { background: #f9f9f9; }
        .final-note { font-weight: bold; background: #d5f5e3 !important; }
        
        /* Résumé final */
        .summary-box { background: #e8f4fc; padding: 20px; border-radius: 8px; margin: 25px 0; border: 2px solid #3498db; }
        .final-grade { font-size: 28px; font-weight: bold; color: #27ae60; text-align: center; margin: 15px 0; }
        .mention { text-align: center; font-size: 20px; font-weight: bold; color: #e74c3c; margin: 10px 0; }
        .decision { text-align: center; font-size: 18px; font-weight: bold; margin: 15px 0; padding: 10px; background: #fff3cd; border-radius: 5px; color: #856404; }
        
        /* Footer */
        .signature { margin-top: 40px; display: flex; justify-content: space-around; }
        .signature-line { width: 200px; border-top: 1px solid #333; padding-top: 5px; text-align: center; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="university-name">UNIVERSITÉ VIRTUELLE</div>
            <div class="faculty-name">{{ $etudiant->niveau->filiere->nom ?? 'Filière non définie' }}</div>
            <div>{{ $bulletin->semestre->anneeAcademique->annee ?? 'Année académique inconnue' }}</div>
        </div>

        <div class="document-title">Bulletin Académique</div>

        <div class="student-info">
            <div class="info-row">
                <span class="info-label">Nom et prénom :</span>
                <span class="info-value">{{ $etudiant->nom_complet }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Matricule :</span>
                <span class="info-value">{{ $etudiant->matricule }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Niveau :</span>
                <span class="info-value">{{ $etudiant->niveau->nom ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Période :</span>
                <span class="info-value">
                    @if($bulletin->semestre_id)
                        Semestre {{ $bulletin->semestre->numero }}
                    @else
                        Année Complète
                    @endif
                </span>
            </div>
        </div>

        <div class="notes-section">
            <div class="section-title">Détail des Notes</div>
            
            @foreach($notesParCours as $coursData)
                <table class="course-table">
                    <tr class="course-header">
                        <td colspan="4">{{ $coursData['cours']->code }} - {{ $coursData['cours']->titre }}</td>
                    </tr>
                    <tr>
                        <th>Type d'évaluation</th>
                        <th>Note</th>
                        <th>Coefficient</th>
                        <th>Note pondérée</th>
                    </tr>
                    @php
                        $totalPondere = 0;
                        $totalCoeff = 0;
                    @endphp
                    @foreach($coursData['notes'] as $note)
                        @php
                            $noteValeur = $note->is_absent ? 0 : $note->note;
                            $coeff = $note->evaluation->coefficient;
                            $pondere = $noteValeur * $coeff;
                            $totalPondere += $pondere;
                            $totalCoeff += $coeff;
                        @endphp
                        <tr class="evaluation-row">
                            <td>{{ $note->evaluation->typeEvaluation->nom }}</td>
                            <td>{{ $note->is_absent ? 'ABS' : number_format($noteValeur, 2) }}</td>
                            <td>{{ number_format($coeff, 2) }}</td>
                            <td>{{ number_format($pondere, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="final-note">
                        <td colspan="3">Note finale du cours</td>
                        <td>{{ $totalCoeff > 0 ? number_format($totalPondere / $totalCoeff, 2) : 'N/A' }}</td>
                    </tr>
                </table>
            @endforeach
        </div>

        <div class="summary-box">
            <div class="final-grade">
                Moyenne Générale : {{ number_format($bulletin->moyenne_generale, 2) }}/20
            </div>
            
            <div class="mention">
                Mention : {{ $mention }}
            </div>
            
            <div class="decision">
                Décision : 
                @if($bulletin->decision instanceof \App\Enums\DecisionBulletin)
                    {{ $bulletin->decision->label() }}
                @else
                    {{ ucfirst((string)$bulletin->decision) }}
                @endif
            </div>
        </div>

        <div class="signature">
            <div class="signature-line">Le Chef de Département</div>
            <div class="signature-line">Le Directeur des Études</div>
        </div>

        <div class="footer">
            Document généré automatiquement - Ne nécessite pas de signature manuelle<br>
            Date de génération : {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>