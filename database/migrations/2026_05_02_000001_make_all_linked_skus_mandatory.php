<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('pb_sku_linked_skus')->update(['mandatory' => true]);
    }

    public function down(): void
    {
        DB::table('pb_sku_linked_skus')->update(['mandatory' => false]);
    }
};
