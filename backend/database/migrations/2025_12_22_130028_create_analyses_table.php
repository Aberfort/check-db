<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();

            $table->string('status', 32)->default('queued'); // queued|processing|success|error
            $table->unsignedTinyInteger('progress')->default(0);

            $table->string('original_name')->nullable();
            $table->string('stored_path')->nullable();
            $table->string('db_type', 32)->nullable();

            $table->string('profile', 32)->default('standard');
            $table->unsignedTinyInteger('score')->nullable();

            $table->json('summary')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
