<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_deal_group_cpl_fuel_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_group_id')->index();
            $table->string('pos_grade', 10);
            $table->decimal('cpl_discount_on_fuel', 6, 1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_deal_group_cpl_fuel_discounts');
    }
};
