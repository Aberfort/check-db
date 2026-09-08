<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('findings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analysis_id')->constrained()->cascadeOnDelete();

            $table->string('check_key', 100);
            $table->string('severity', 16)->default('warning'); // critical|warning|info

            $table->string('table_name')->nullable();
            $table->string('column_name')->nullable();
            $table->json('row_ref')->nullable();

            // Stored language-neutral so the UI language can change without re-running.
            $table->string('message_key', 100);
            $table->json('message_params')->nullable();

            // Grouping label (e.g. an HTTP status) so histograms aggregate in SQL.
            $table->string('bucket', 64)->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['analysis_id', 'check_key']);
            $table->index(['analysis_id', 'severity']);
            $table->index(['analysis_id', 'check_key', 'bucket']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('findings');
    }
};
