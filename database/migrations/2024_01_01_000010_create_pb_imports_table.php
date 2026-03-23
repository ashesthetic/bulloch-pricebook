<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_imports', function (Blueprint $table) {
            $table->id();
            $table->string('file_path', 500);
            $table->string('bt9000_version', 20)->nullable();
            $table->string('generated_by', 100)->nullable();
            $table->unsignedInteger('station_id')->nullable();
            $table->string('file_creation_date', 12)->nullable();
            $table->dateTime('file_created_at')->nullable();
            $table->json('records_imported')->nullable();
            $table->unsignedInteger('total_records')->nullable();
            $table->unsignedInteger('processed_records')->default(0);
            $table->unsignedTinyInteger('progress_percentage')->default(0);
            $table->string('current_section', 50)->nullable();
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_imports');
    }
};
