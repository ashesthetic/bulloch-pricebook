<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_deal_groups', function (Blueprint $table) {
            $table->id();
            $table->string('deal_group_number', 13)->index();
            $table->enum('type', ['site', 'head_office', 'home_office']);
            $table->unique(['deal_group_number', 'type']);
            $table->string('english_description', 18)->nullable();
            $table->string('french_description', 18)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('fuel_mix_and_match_check')->nullable();
            $table->boolean('dont_calculate_deal')->nullable();
            $table->boolean('deal_not_active')->nullable()->index();
            $table->boolean('available_in_kiosk_only')->nullable();
            $table->boolean('cpl_stacking_cpn')->nullable();
            $table->boolean('available_at_pump_only')->nullable();
            $table->unsignedTinyInteger('reason_code_for_deal')->nullable();
            $table->string('station_id_for_deal', 7)->nullable();
            $table->decimal('fixed_dollar_off', 5, 2)->nullable();
            $table->unsignedSmallInteger('max_per_customer')->nullable();
            $table->string('req_fuel_pos_grade', 20)->nullable();
            $table->unsignedInteger('req_fuel_litres')->nullable();
            $table->string('loyalty_card_description', 18)->nullable();
            $table->boolean('loyalty_card_restriction')->nullable();
            $table->unsignedTinyInteger('loyalty_card_swipe_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_deal_groups');
    }
};
