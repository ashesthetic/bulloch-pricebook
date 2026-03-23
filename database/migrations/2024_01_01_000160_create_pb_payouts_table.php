<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_payouts', function (Blueprint $table) {
            $table->string('payout_number', 13)->primary();
            $table->string('english_description', 18);
            $table->string('french_description', 18)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_payouts');
    }
};
