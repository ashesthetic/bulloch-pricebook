<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_sku_upcs', function (Blueprint $table) {
            $table->string('upc', 13)->nullable()->change();
        });

        Schema::table('pb_sku_quantity_pricing', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->nullable()->change();
            $table->decimal('price', 8, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pb_sku_upcs', function (Blueprint $table) {
            $table->string('upc', 13)->nullable(false)->change();
        });

        Schema::table('pb_sku_quantity_pricing', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->nullable(false)->change();
            $table->decimal('price', 8, 2)->nullable(false)->change();
        });
    }
};
