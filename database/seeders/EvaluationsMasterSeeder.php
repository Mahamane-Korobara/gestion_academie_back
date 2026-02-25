<?php

namespace Database\Seeders;

use App\Models\Cours;
use App\Models\Evaluation;
use App\Models\TypeEvaluation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EvaluationsMasterSeeder extends Seeder
{
    public function run(): void
    {
        $types = TypeEvaluation::query()
            ->whereIn('code', ['CC', 'EF', 'TP', 'PROJ', 'RATT'])
            ->get()
            ->keyBy('code');

        $codesManquants = collect(['CC', 'EF', 'TP', 'PROJ', 'RATT'])
            ->reject(fn (string $code) => $types->has($code))
            ->values();

        if ($codesManquants->isNotEmpty()) {
            $this->command?->warn(
                'Types d evaluation manquants: ' . $codesManquants->implode(', ') . '. Seeder ignore.'
            );
            return;
        }

        $coursList = Cours::query()
            ->with(['semestre'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $createdOrUpdated = 0;

        DB::transaction(function () use ($coursList, $types, &$createdOrUpdated): void {
            foreach ($coursList as $cours) {
                if (!$cours->semestre) {
                    continue;
                }

                $plan = $this->buildPlanForCours($cours->titre, $cours->code);

                foreach ($plan as $row) {
                    $type = $types->get($row['type_code']);
                    if (!$type) {
                        continue;
                    }

                    $date = $this->resolveDate(
                        $cours->semestre->date_debut?->copy(),
                        $cours->semestre->date_fin?->copy(),
                        $cours->semestre->date_debut_examens?->copy(),
                        $cours->semestre->date_fin_examens?->copy(),
                        $row['type_code'],
                        (int) $cours->id
                    );

                    $attributes = [
                        'cours_id' => $cours->id,
                        'type_evaluation_id' => $type->id,
                        'semestre_id' => $cours->semestre_id,
                        'titre' => $row['titre'],
                    ];

                    Evaluation::updateOrCreate(
                        $attributes,
                        [
                            'coefficient' => $row['coefficient'],
                            'date_evaluation' => $date->toDateString(),
                            'heure_debut' => $row['heure_debut'],
                            'heure_fin' => $row['heure_fin'],
                            'instructions' => $row['instructions'],
                            'statut' => $this->resolveStatut($date),
                        ]
                    );

                    $createdOrUpdated++;
                }
            }
        });

        $this->command?->info(
            "Evaluations master seedees: {$createdOrUpdated} evaluations creees/mises a jour."
        );
    }

    /**
     * @return array<int, array{type_code:string,titre:string,coefficient:float,heure_debut:string,heure_fin:string,instructions:string}>
     */
    private function buildPlanForCours(string $titreCours, string $codeCours): array
    {
        $normalized = Str::lower(Str::ascii($titreCours . ' ' . $codeCours));

        if ($this->isMemoireLike($normalized)) {
            return [
                [
                    'type_code' => 'PROJ',
                    'titre' => 'Memoire / dossier professionnel',
                    'coefficient' => 0.60,
                    'heure_debut' => '09:00',
                    'heure_fin' => '12:00',
                    'instructions' => 'Depot du document final et soutenance technique/professionnelle.',
                ],
                [
                    'type_code' => 'EF',
                    'titre' => 'Soutenance finale',
                    'coefficient' => 0.40,
                    'heure_debut' => '14:00',
                    'heure_fin' => '17:00',
                    'instructions' => 'Presentation orale devant jury (15 min expose + questions/reponses).',
                ],
                [
                    'type_code' => 'RATT',
                    'titre' => 'Session de rattrapage',
                    'coefficient' => 1.00,
                    'heure_debut' => '09:00',
                    'heure_fin' => '11:00',
                    'instructions' => 'Session de rattrapage reservee aux etudiants non valides en session normale.',
                ],
            ];
        }

        if ($this->isPratiqueLike($normalized)) {
            return [
                [
                    'type_code' => 'CC',
                    'titre' => 'Controle continu',
                    'coefficient' => 0.30,
                    'heure_debut' => '08:00',
                    'heure_fin' => '10:00',
                    'instructions' => 'Epreuve ecrite courte sur les chapitres traites.',
                ],
                [
                    'type_code' => 'TP',
                    'titre' => 'Travaux pratiques / etude de cas',
                    'coefficient' => 0.20,
                    'heure_debut' => '14:00',
                    'heure_fin' => '17:00',
                    'instructions' => 'Evaluation pratique en salle informatique ou etude de cas appliquee.',
                ],
                [
                    'type_code' => 'EF',
                    'titre' => 'Examen final',
                    'coefficient' => 0.50,
                    'heure_debut' => '08:00',
                    'heure_fin' => '11:00',
                    'instructions' => 'Examen final de semestre (sujet + resolution argumentee).',
                ],
                [
                    'type_code' => 'RATT',
                    'titre' => 'Session de rattrapage',
                    'coefficient' => 1.00,
                    'heure_debut' => '10:30',
                    'heure_fin' => '12:30',
                    'instructions' => 'Session de rattrapage reservee aux etudiants non valides en session normale.',
                ],
            ];
        }

        return [
            [
                'type_code' => 'CC',
                'titre' => 'Controle continu',
                'coefficient' => 0.40,
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'instructions' => 'Epreuve ecrite de mi-semestre.',
            ],
            [
                'type_code' => 'EF',
                'titre' => 'Examen final',
                'coefficient' => 0.60,
                'heure_debut' => '08:00',
                'heure_fin' => '11:00',
                'instructions' => 'Examen final de semestre.',
            ],
            [
                'type_code' => 'RATT',
                'titre' => 'Session de rattrapage',
                'coefficient' => 1.00,
                'heure_debut' => '10:00',
                'heure_fin' => '12:00',
                'instructions' => 'Session de rattrapage reservee aux etudiants non valides en session normale.',
            ],
        ];
    }

    private function isMemoireLike(string $normalized): bool
    {
        return Str::contains($normalized, [
            'memoire',
            'soutenance',
            'projet de fin',
            'pfe',
            ' mpa',
            ' mpd',
            '-mem',
        ]);
    }

    private function isPratiqueLike(string $normalized): bool
    {
        return Str::contains($normalized, [
            'architecture',
            'administration',
            'donnees',
            'big data',
            'bi',
            'cloud',
            'virtualisation',
            'reseau',
            'telecom',
            '5g',
            'iot',
            'securite',
            'cyber',
            'audit',
            'voip',
            'ingenierie',
        ]);
    }

    private function resolveDate(
        ?Carbon $dateDebut,
        ?Carbon $dateFin,
        ?Carbon $dateDebutExamens,
        ?Carbon $dateFinExamens,
        string $typeCode,
        int $seed
    ): Carbon {
        $start = ($dateDebut ?? now())->copy()->startOfDay();
        $end = ($dateFin ?? now()->addMonths(4))->copy()->startOfDay();

        if ($end->lt($start)) {
            $end = $start->copy()->addDays(90);
        }

        $examStart = ($dateDebutExamens ?? $end->copy()->subDays(12))->copy()->startOfDay();
        $examEnd = ($dateFinExamens ?? $end->copy()->subDays(2))->copy()->startOfDay();

        if ($examEnd->lt($examStart)) {
            $examEnd = $examStart->copy()->addDays(2);
        }

        $examSpan = max(1, $examStart->diffInDays($examEnd));

        $candidate = match ($typeCode) {
            'CC' => $start->copy()->addDays(35 + ($seed % 14)),
            'TP', 'PROJ' => $start->copy()->addDays(55 + ($seed % 18)),
            'EF' => $examStart->copy()->addDays($seed % ($examSpan + 1)),
            'RATT' => $examEnd->copy()->subDays($seed % 3),
            default => $end->copy()->subDays(7),
        };

        if ($candidate->lt($start)) {
            return $start;
        }

        if ($candidate->gt($end)) {
            return $end;
        }

        return $candidate;
    }

    private function resolveStatut(Carbon $date): string
    {
        $today = now()->startOfDay();

        if ($date->lt($today)) {
            return 'terminee';
        }

        if ($date->equalTo($today)) {
            return 'en_cours';
        }

        return 'planifiee';
    }
}
