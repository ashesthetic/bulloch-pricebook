<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pb_deal_group_component_upcs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deal_group_component_id')->index();
            $table->string('upc', 13);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pb_deal_group_component_upcs');
    }
};
