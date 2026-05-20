<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifier_queue_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('item_number', 13);
            $table->foreign('item_number')->references('item_number')->on('pb_skus')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'item_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modifier_queue_items');
    }
};
