<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_deal_groups', function (Blueprint $table) {
            $table->string('english_afd_car_wash_message', 80)->nullable()->after('french_description');
            $table->string('french_afd_car_wash_message', 80)->nullable()->after('english_afd_car_wash_message');
        });
    }

    public function down(): void
    {
        Schema::table('pb_deal_groups', function (Blueprint $table) {
            $table->dropColumn(['english_afd_car_wash_message', 'french_afd_car_wash_message']);
        });
    }
};
