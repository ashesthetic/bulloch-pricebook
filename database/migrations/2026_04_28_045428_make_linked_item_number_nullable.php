<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_sku_linked_skus', function (Blueprint $table) {
            $table->string('linked_item_number', 13)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pb_sku_linked_skus', function (Blueprint $table) {
            $table->string('linked_item_number', 13)->nullable(false)->change();
        });
    }
};
