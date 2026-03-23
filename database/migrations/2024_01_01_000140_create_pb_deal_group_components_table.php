<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_deal_group_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_group_id')->index();
            $table->string('item_number', 13)->nullable()->index();
            $table->string('price_group_number', 13)->nullable()->index();
            $table->string('mix_and_match_identifier', 13)->nullable()->index();
            $table->unsignedSmallInteger('quantity');
            $table->decimal('price_for_quantity_one', 8, 2);
            $table->unsignedTinyInteger('percentage_off')->nullable();
            $table->decimal('amount_off', 8, 2)->nullable();
            $table->string('coupon_accounting_implications', 6)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_deal_group_components');
    }
};
