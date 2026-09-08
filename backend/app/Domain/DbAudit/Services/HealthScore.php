<?php

namespace App\Domain\DbAudit\Services;

use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;

/**
 * The score answers "how much of what we checked came back clean", weighted by
 * how much each check matters. It deliberately does not scale with row count:
 * a 200-row database and a 20-million-row database with the same problems get
 * the same score, so two runs can be compared.
 *
 * A failed check costs its full weight for critical findings and a fraction of
 * it for warnings. Informational findings never cost anything.
 */
class HealthScore
{
    /**
     * @param  array<string, CheckResult>  $results
     * @param  array<string, DbCheck>  $checks
     * @return array{score:int, grade:string, checks_passed:int, checks_run:int}
     */
    public function compute(array $results, array $checks): array
    {
        $totalWeight = 0.0;
        $lostWeight = 0.0;
        $passed = 0;
        $run = 0;

        foreach ($results as $key => $result) {
            if ($result->skipped) {
                continue;
            }

            $weight = isset($checks[$key]) ? $checks[$key]->weight() : 1.0;

            $totalWeight += $weight;
            $run++;

            $worst = $result->worstSeverity();

            if ($worst === null || $worst === Severity::INFO) {
                $passed++;

                continue;
            }

            $lostWeight += $weight * (Severity::WEIGHTS[$worst] ?? 1.0);
        }

        $score = $totalWeight > 0.0
            ? (int) round(100 * (1 - ($lostWeight / $totalWeight)))
            : 100;

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'grade' => $this->grade($score),
            'checks_passed' => $passed,
            'checks_run' => $run,
        ];
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'good',
            $score >= 70 => 'fair',
            default => 'poor',
        };
    }
}
