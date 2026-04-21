<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pb_tenders_coupons', function (Blueprint $table) {
            $table->boolean('prompt_for_amount')->nullable()->after('french_description');
            $table->unsignedTinyInteger('tender_type')->nullable()->after('prompt_for_amount');
            $table->decimal('amount', 5, 2)->nullable()->after('tender_type');
            $table->string('loyalty_card_description', 18)->nullable()->after('amount');
            $table->boolean('loyalty_card_restriction')->nullable()->after('loyalty_card_description');
            $table->unsignedTinyInteger('loyalty_card_swipe_type')->nullable()->after('loyalty_card_restriction');
            $table->unsignedInteger('type_of_restrictions')->nullable()->after('loyalty_card_swipe_type');
            $table->string('restriction_identifier', 13)->nullable()->after('type_of_restrictions');
            $table->boolean('available_at_pump_only')->nullable()->after('restriction_identifier');
            $table->boolean('available_in_kiosk_only')->nullable()->after('available_at_pump_only');
            $table->boolean('coupon_not_active')->nullable()->index()->after('available_in_kiosk_only');
            $table->unsignedSmallInteger('max_per_customer')->nullable()->after('coupon_not_active');
            $table->date('start_date')->nullable()->after('max_per_customer');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('coupon_accounting_implications', 6)->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('pb_tenders_coupons', function (Blueprint $table) {
            $table->dropColumn([
                'prompt_for_amount',
                'tender_type',
                'amount',
                'loyalty_card_description',
                'loyalty_card_restriction',
                'loyalty_card_swipe_type',
                'type_of_restrictions',
                'restriction_identifier',
                'available_at_pump_only',
                'available_in_kiosk_only',
                'coupon_not_active',
                'max_per_customer',
                'start_date',
                'end_date',
                'coupon_accounting_implications',
            ]);
        });
    }
};
