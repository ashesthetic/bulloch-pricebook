<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_sku_linkable_skus', function (Blueprint $table) {
            $table->id();
            $table->string('item_number', 13)->index();
            $table->string('linkable_item_number', 13)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_sku_linkable_skus');
    }
};
