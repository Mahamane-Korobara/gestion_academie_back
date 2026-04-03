<?php

namespace Database\Seeders;

use App\Models\Cours;
use App\Models\Evaluation;
use App\Models\TypeEvaluation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EvaluationsMasterSeeder extends Seeder
{
    public function run(): void
    {
        $types = TypeEvaluation::query()
            ->whereIn('code', ['EF'])
            ->get()
            ->keyBy('code');

        $codesManquants = collect(['EF'])
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
        return [
            [
                'type_code' => 'EF',
                'titre' => 'Examen',
                'coefficient' => 1.00,
                'heure_debut' => '08:00',
                'heure_fin' => '11:00',
                'instructions' => 'Examen de fin de semestre.',
            ],
        ];
    }

    private function resolveDate(
        ?Carbon $dateDebut,
        ?Carbon $dateFin,
        ?Carbon $dateDebutExamens,
        ?Carbon $dateFinExamens,
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

        $candidate = $examStart->copy()->addDays($seed % ($examSpan + 1));

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
