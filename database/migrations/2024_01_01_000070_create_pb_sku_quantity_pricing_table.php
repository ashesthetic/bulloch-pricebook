<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_sku_quantity_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('item_number', 13)->index();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('price', 8, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_sku_quantity_pricing');
    }
};
