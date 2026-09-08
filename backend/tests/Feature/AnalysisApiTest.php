<?php

namespace Tests\Feature;

use App\Models\Analysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use App\Jobs\ProcessAnalysisJob;
use PDO;
use Tests\TestCase;

class AnalysisApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_an_upload_and_queues_the_analysis(): void
    {
        Storage::fake('local');
        Queue::fake();

        $response = $this->postJson('/api/analyses', [
            'file' => $this->sqliteUpload(),
            'profile' => 'quick',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'queued')
            ->assertJsonPath('data.profile', 'quick');

        Queue::assertPushed(ProcessAnalysisJob::class);
    }

    public function test_it_rejects_a_file_type_it_cannot_read(): void
    {
        Storage::fake('local');

        $this->postJson('/api/analyses', [
            'file' => UploadedFile::fake()->create('notes.txt', 4, 'text/plain'),
        ])->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_it_rejects_an_unknown_profile(): void
    {
        Storage::fake('local');

        $this->postJson('/api/analyses', [
            'file' => $this->sqliteUpload(),
            'profile' => 'does-not-exist',
        ])->assertStatus(422);
    }

    public function test_it_stores_the_requested_profile_rather_than_the_default(): void
    {
        Storage::fake('local');
        Queue::fake();

        $this->postJson('/api/analyses', [
            'file' => $this->sqliteUpload(),
            'profile' => 'thorough',
        ])->assertCreated();

        $this->assertSame('thorough', Analysis::first()->profile);
    }

    public function test_it_runs_a_full_analysis_and_reports_the_problems_it_found(): void
    {
        Storage::fake('local');

        $this->postJson('/api/analyses', [
            'file' => $this->sqliteUpload(),
            'profile' => 'standard',
        ])->assertCreated();

        // The queue runs synchronously under the test configuration.
        $analysis = Analysis::first();

        $this->assertSame('success', $analysis->status);
        $this->assertSame(100, $analysis->progress);

        $checks = $analysis->summary['checks'];
        $this->assertSame('failed', $checks['foreign_keys']['status']);
        $this->assertSame('passed', $checks['integrity']['status']);
        $this->assertLessThan(100, $analysis->score);
    }

    public function test_it_renders_finding_messages_in_the_requested_language(): void
    {
        Storage::fake('local');

        $this->postJson('/api/analyses', ['file' => $this->sqliteUpload()])->assertCreated();
        $id = Analysis::first()->id;

        $english = $this->getJson("/api/analyses/{$id}/findings?check_key=foreign_keys");
        $ukrainian = $this->getJson("/api/analyses/{$id}/findings?check_key=foreign_keys&lang=uk");

        $english->assertOk();
        $this->assertStringContainsString('references a missing', $english->json('data.0.message'));
        $this->assertStringContainsString('посилається', $ukrainian->json('data.0.message'));
    }

    public function test_it_aggregates_findings_without_paging_through_them(): void
    {
        Storage::fake('local');

        $this->postJson('/api/analyses', ['file' => $this->sqliteUpload()])->assertCreated();
        $id = Analysis::first()->id;

        $this->getJson("/api/analyses/{$id}/findings/summary")
            ->assertOk()
            ->assertJsonPath('data.by_check.foreign_keys.critical', 1);
    }

    public function test_it_returns_a_structured_error_for_a_missing_analysis(): void
    {
        $this->getJson('/api/analyses/999')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_it_publishes_the_check_catalogue(): void
    {
        $this->getJson('/api/meta')
            ->assertOk()
            ->assertJsonPath('data.default_profile', 'standard')
            ->assertJsonPath('data.checks.foreign_keys.title', 'Foreign key violations');
    }

    /** A tiny database carrying one deliberate orphan row. */
    private function sqliteUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'fixture') . '.sqlite';

        $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('CREATE TABLE customers (id INTEGER PRIMARY KEY, email TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE orders (id INTEGER PRIMARY KEY, customer_id INTEGER REFERENCES customers(id))');
        $pdo->exec("INSERT INTO customers (id, email) VALUES (1, 'a@example.test')");
        $pdo->exec('INSERT INTO orders (id, customer_id) VALUES (1, 1), (2, 404)');
        $pdo = null;

        return new UploadedFile($path, 'fixture.sqlite', 'application/x-sqlite3', null, true);
    }
}
