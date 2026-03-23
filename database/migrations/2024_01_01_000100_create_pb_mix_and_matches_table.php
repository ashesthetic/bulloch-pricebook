<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_mix_and_matches', function (Blueprint $table) {
            $table->string('mix_and_match_identifier', 13)->primary();
            $table->string('english_description', 18)->nullable();
            $table->string('french_description', 18)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_mix_and_matches');
    }
};
