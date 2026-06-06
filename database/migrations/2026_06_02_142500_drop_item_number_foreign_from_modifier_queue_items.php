<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifier_queue_items', function (Blueprint $table) {
            $table->dropForeign('modifier_queue_items_item_number_foreign');
        });
    }

    public function down(): void
    {
        Schema::table('modifier_queue_items', function (Blueprint $table) {
            $table->foreign('item_number')->references('item_number')->on('pb_skus')->cascadeOnDelete();
        });
    }
};
