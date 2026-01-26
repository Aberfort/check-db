<?php

namespace App\Domain\DbAudit\Services;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Models\Analysis;
use App\Models\Finding;

class AnalysisRunner
{
    /**
     * @param array<int, object> $checks масив DbCheck (інстанси)
     * @return array<string, mixed>
     */
    public function run(Analysis $analysis, DbAdapter $db, array $checks): array
    {
        Finding::query()->where('analysis_id', $analysis->id)->delete();

        $issuesByType = [];
        $severity = ['ok' => 0, 'warning' => 0, 'critical' => 0];

        $totalChecks = count($checks);
        $completed = 0;

        foreach ($checks as $check) {
            $result = $check->run($db);

            $issuesByType[$result->checkKey] = [
                'title' => $result->title,
                'count' => count($result->findings),
            ];

            foreach ($result->findings as $f) {
                $sev = in_array($f->severity, ['ok', 'warning', 'critical'], true) ? $f->severity : 'warning';
                $severity[$sev] = ($severity[$sev] ?? 0) + 1;

                Finding::create([
                    'analysis_id' => $analysis->id,
                    'check_key' => $result->checkKey,
                    'severity' => $sev,
                    'table_name' => $f->table,
                    'column_name' => $f->column,
                    'row_ref' => $f->rowRef,
                    'message' => $f->message,
                    'meta' => $f->meta,
                ]);
            }

            $completed++;
            $analysis->update([
                'progress' => 20 + (int) floor(($completed / max(1, $totalChecks)) * 70), // 20..90
            ]);
        }

        $totalIssues = (int) (($severity['warning'] ?? 0) + ($severity['critical'] ?? 0));

        // Health score (MVP): critical важить сильніше за warning
        $crit = (int) ($severity['critical'] ?? 0);
        $warn = (int) ($severity['warning'] ?? 0);

        $score = max(0, 100 - ($crit * 10) - ($warn * 2));
        $label = $score >= 90 ? 'OK' : ($score >= 70 ? 'Warning' : 'Critical');

        $analysis->update(['score' => $score]);

        return [
            'issues_by_type' => $issuesByType,
            'severity' => $severity,
            'overview' => [
                'total_checks' => $totalChecks,
                'total_issues' => $totalIssues,
            ],
            'health' => [
                'score' => $score,
                'label' => $label,
            ],
        ];
    }
}
