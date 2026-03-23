<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_loyalty_card_bins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loyalty_card_id')->index();
            $table->string('start_iso_bin', 20);
            $table->string('end_iso_bin', 20);
            $table->unsignedTinyInteger('min_length');
            $table->unsignedTinyInteger('max_length');
            $table->unsignedTinyInteger('check_digit');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_loyalty_card_bins');
    }
};
