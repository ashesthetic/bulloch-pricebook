<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_price_groups', function (Blueprint $table) {
            $table->string('price_group_number', 13)->primary();
            $table->string('english_description', 18);
            $table->string('french_description', 18)->nullable();
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_price_groups');
    }
};
