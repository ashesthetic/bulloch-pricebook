<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_departments', function (Blueprint $table) {
            $table->string('department_number', 6)->primary();
            $table->string('description', 18);
            $table->boolean('shift_report_flag')->default(false);
            $table->boolean('sales_summary_report')->default(false);
            $table->string('owner', 100)->nullable();
            $table->boolean('bt9000_inventory_control')->nullable();
            $table->string('conexxus_product_code', 10)->nullable();
            $table->boolean('gift_card_department')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_departments');
    }
};
