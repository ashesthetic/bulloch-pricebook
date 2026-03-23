<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_skus', function (Blueprint $table) {
            $table->string('item_number', 13)->primary();
            $table->string('english_description', 18);
            $table->string('french_description', 18)->nullable();
            $table->decimal('price', 8, 2);
            $table->string('department_number', 6)->index();
            $table->string('price_group_number', 13)->nullable()->index();
            $table->decimal('item_deposit', 8, 2)->nullable();
            $table->string('promo_code', 12)->nullable();
            $table->string('host_product_code', 50)->nullable();
            $table->boolean('tax1')->nullable();
            $table->boolean('tax2')->nullable();
            $table->boolean('tax3')->nullable();
            $table->boolean('tax4')->nullable();
            $table->boolean('tax5')->nullable();
            $table->boolean('tax6')->nullable();
            $table->boolean('tax7')->nullable();
            $table->boolean('tax8')->nullable();
            $table->boolean('prompt_for_price')->nullable();
            $table->boolean('item_not_active')->nullable()->index();
            $table->boolean('tax_included_price')->nullable();
            $table->char('wash_type', 1)->nullable();
            $table->unsignedInteger('car_wash_controller_code')->nullable();
            $table->unsignedTinyInteger('upsell_qty_car_wash')->nullable();
            $table->unsignedTinyInteger('petro_canada_pass_code')->nullable();
            $table->boolean('item_desc_not_on_2nd_monitor')->nullable();
            $table->boolean('ontario_rst_tax_off')->nullable();
            $table->boolean('ontario_rst_tax_on')->nullable();
            $table->boolean('federal_baked_good_item')->nullable();
            $table->boolean('prevent_bt9000_inventory_control')->nullable();
            $table->string('conexxus_product_code', 10)->nullable();
            $table->unsignedSmallInteger('car_wash_expiry_in_days')->nullable();
            $table->unsignedTinyInteger('afd_car_wash_position')->nullable();
            $table->unsignedTinyInteger('age_requirements')->nullable();
            $table->boolean('redemption_only')->nullable();
            $table->boolean('loyalty_card_eligible')->default(false)->index();
            $table->decimal('delivery_channel_price', 8, 2)->nullable();
            $table->string('tax_strategy_id_from_nacs', 20)->nullable();
            $table->string('owner', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_skus');
    }
};
