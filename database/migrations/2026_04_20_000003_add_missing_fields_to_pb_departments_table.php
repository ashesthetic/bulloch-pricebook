<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_departments', function (Blueprint $table) {
            $table->unsignedTinyInteger('age_requirements')->nullable()->after('gift_card_department');
            $table->string('default_item', 13)->nullable()->after('age_requirements');
        });
    }

    public function down(): void
    {
        Schema::table('pb_departments', function (Blueprint $table) {
            $table->dropColumn(['age_requirements', 'default_item']);
        });
    }
};
