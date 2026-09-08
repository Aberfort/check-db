<?php

namespace Tests\Unit;

use App\Domain\DbAudit\Contracts\DbAdapter;
use App\Domain\DbAudit\Contracts\DbCheck;
use App\Domain\DbAudit\DTO\CheckFinding;
use App\Domain\DbAudit\DTO\CheckResult;
use App\Domain\DbAudit\DTO\Severity;
use App\Domain\DbAudit\Services\HealthScore;
use Tests\TestCase;

class HealthScoreTest extends TestCase
{
    public function test_a_clean_database_scores_one_hundred(): void
    {
        $score = (new HealthScore)->compute(
            ['a' => new CheckResult('a'), 'b' => new CheckResult('b')],
            ['a' => $this->check('a', 1.0), 'b' => $this->check('b', 1.0)],
        );

        $this->assertSame(100, $score['score']);
        $this->assertSame('good', $score['grade']);
        $this->assertSame(2, $score['checks_passed']);
    }

    public function test_a_critical_finding_costs_the_full_weight_of_its_check(): void
    {
        $failed = new CheckResult('a');
        $failed->add(new CheckFinding(Severity::CRITICAL, 'x'));

        $score = (new HealthScore)->compute(
            ['a' => $failed, 'b' => new CheckResult('b')],
            ['a' => $this->check('a', 1.0), 'b' => $this->check('b', 1.0)],
        );

        $this->assertSame(50, $score['score']);
    }

    public function test_a_warning_costs_less_than_a_critical(): void
    {
        $warned = new CheckResult('a');
        $warned->add(new CheckFinding(Severity::WARNING, 'x'));

        $score = (new HealthScore)->compute(
            ['a' => $warned, 'b' => new CheckResult('b')],
            ['a' => $this->check('a', 1.0), 'b' => $this->check('b', 1.0)],
        );

        $this->assertSame(80, $score['score']);
    }

    public function test_informational_findings_do_not_reduce_the_score(): void
    {
        $noticed = new CheckResult('a');
        $noticed->add(new CheckFinding(Severity::INFO, 'x'));

        $score = (new HealthScore)->compute(
            ['a' => $noticed],
            ['a' => $this->check('a', 1.0)],
        );

        $this->assertSame(100, $score['score']);
        $this->assertSame(1, $score['checks_passed']);
    }

    public function test_skipped_checks_are_left_out_of_the_calculation(): void
    {
        $skipped = (new CheckResult('b'))->skip();

        $score = (new HealthScore)->compute(
            ['a' => new CheckResult('a'), 'b' => $skipped],
            ['a' => $this->check('a', 1.0), 'b' => $this->check('b', 5.0)],
        );

        $this->assertSame(100, $score['score']);
        $this->assertSame(1, $score['checks_run']);
    }

    /**
     * The score describes how much of what was checked came back clean, so it
     * must not move just because one database holds more rows than another.
     */
    public function test_the_score_does_not_depend_on_how_many_findings_a_check_produced(): void
    {
        $one = new CheckResult('a');
        $one->add(new CheckFinding(Severity::CRITICAL, 'x'));

        $many = new CheckResult('a');
        for ($i = 0; $i < 400; $i++) {
            $many->add(new CheckFinding(Severity::CRITICAL, 'x'));
        }

        $checks = ['a' => $this->check('a', 1.0), 'b' => $this->check('b', 1.0)];

        $this->assertSame(
            (new HealthScore)->compute(['a' => $one, 'b' => new CheckResult('b')], $checks)['score'],
            (new HealthScore)->compute(['a' => $many, 'b' => new CheckResult('b')], $checks)['score'],
        );
    }

    private function check(string $key, float $weight): DbCheck
    {
        return new class($key, $weight) implements DbCheck
        {
            public function __construct(private string $key, private float $weight) {}

            public function key(): string
            {
                return $this->key;
            }

            public function weight(): float
            {
                return $this->weight;
            }

            public function run(DbAdapter $db): CheckResult
            {
                return new CheckResult($this->key);
            }
        };
    }
}
