<?php

namespace App\Domain\DbAudit\Services;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;
use App\Models\Analysis;
use App\Models\Finding;
use Illuminate\Support\Carbon;

class AnalysisRunner
{
    private const INSERT_CHUNK = 500;

    public function __construct(private HealthScore $healthScore) {}

    /**
     * @param  array<string, DbCheck>  $checks  keyed by check key
     * @param  callable(int):void|null  $onProgress  receives 0..100 of the check phase
     * @return array<string, mixed> the analysis summary
     */
    public function run(Analysis $analysis, DbAdapter $db, array $checks, ?callable $onProgress = null): array
    {
        $analysis->findings()->delete();

        $schema = $this->readSchema($db);

        /** @var array<string, CheckResult> $results */
        $results = [];
        $severityTotals = array_fill_keys(Severity::ALL, 0);
        $pending = [];
        $done = 0;

        foreach ($checks as $key => $check) {
            $result = $check->run($db);
            $results[$key] = $result;

            foreach ($result->findings as $finding) {
                $severity = Severity::isValid($finding->severity) ? $finding->severity : Severity::WARNING;
                $severityTotals[$severity]++;

                $pending[] = [
                    'analysis_id' => $analysis->id,
                    'check_key' => $key,
                    'severity' => $severity,
                    'table_name' => $finding->table,
                    'column_name' => $finding->column,
                    'row_ref' => $finding->rowRef ? json_encode($finding->rowRef, JSON_UNESCAPED_UNICODE) : null,
                    'message_key' => $finding->messageKey,
                    'message_params' => json_encode($finding->params, JSON_UNESCAPED_UNICODE),
                    'bucket' => $finding->bucket,
                    'meta' => $finding->meta ? json_encode($finding->meta, JSON_UNESCAPED_UNICODE) : null,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                if (count($pending) >= self::INSERT_CHUNK) {
                    Finding::insert($pending);
                    $pending = [];
                }
            }

            $done++;

            if ($onProgress !== null) {
                $onProgress((int) floor($done / max(1, count($checks)) * 100));
            }
        }

        if ($pending !== []) {
            Finding::insert($pending);
        }

        return $this->summarise($analysis, $schema, $results, $checks, $severityTotals);
    }

    /** @return array{tables:string[], row_counts:array<string,int>, total_rows:int} */
    private function readSchema(DbAdapter $db): array
    {
        $tables = $db->listTables();
        $counts = [];

        foreach ($tables as $table) {
            try {
                $counts[$table] = $db->count($table);
            } catch (\Throwable) {
                $counts[$table] = 0;
            }
        }

        return [
            'tables' => $tables,
            'row_counts' => $counts,
            'total_rows' => array_sum($counts),
        ];
    }

    /**
     * @param  array<string, CheckResult>  $results
     * @param  array<string, DbCheck>  $checks
     * @param  array<string, int>  $severityTotals
     * @return array<string, mixed>
     */
    private function summarise(
        Analysis $analysis,
        array $schema,
        array $results,
        array $checks,
        array $severityTotals
    ): array {
        $checkSummary = [];

        foreach ($results as $key => $result) {
            $worst = $result->worstSeverity();

            // Informational findings are observations, so a check that only
            // produced those still counts as passing — same rule the score uses.
            $failed = $worst !== null && $worst !== Severity::INFO;

            $checkSummary[$key] = [
                'status' => $result->skipped ? 'skipped' : ($failed ? 'failed' : 'passed'),
                'findings' => count($result->findings),
                'severity' => $worst,
                'truncated' => $result->truncated,
                'weight' => isset($checks[$key]) ? $checks[$key]->weight() : 1.0,
            ];
        }

        return [
            'schema' => [
                'total_tables' => count($schema['tables']),
                'total_rows' => $schema['total_rows'],
                'tables' => $schema['tables'],
                'row_counts' => $schema['row_counts'],
            ],
            'checks' => $checkSummary,
            'severity' => $severityTotals,
            'totals' => [
                'findings' => array_sum($severityTotals),
                // Informational findings are observations, not problems to fix.
                'issues' => $severityTotals[Severity::CRITICAL] + $severityTotals[Severity::WARNING],
            ],
            'health' => $this->healthScore->compute($results, $checks),
            'profile' => $analysis->profile,
        ];
    }
}
