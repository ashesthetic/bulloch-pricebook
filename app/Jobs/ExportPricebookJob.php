<?php

namespace App\Jobs;

use App\Models\Pricebook\PricebookExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ExportPricebookJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(
        private readonly ?string $outputPath = null,
        private readonly ?int $exportId = null,
    ) {}

    public function handle(): void
    {
        $path = $this->outputPath ?? base_path(config('pricebook.path'));
        $tmp  = $path . '.tmp';

        // Resolve or create the export tracking record
        $export = $this->exportId
            ? PricebookExport::findOrFail($this->exportId)
            : PricebookExport::create(['file_path' => $path, 'started_at' => now(), 'status' => 'running']);

        $lastImport = DB::table('pb_imports')
            ->where('status', 'completed')
            ->orderByDesc('id')
            ->first();

        $version     = $lastImport?->bt9000_version ?? '070206';
        $generatedBy = $lastImport?->generated_by ?? 'Bulloch';
        $stationId   = $lastImport?->station_id ?? '';
        $fileDate    = now()->format('YmdHi');

        $fh = fopen($tmp, 'w');
        if ($fh === false) {
            $export->update(['status' => 'failed', 'finished_at' => now(), 'error_message' => "Cannot open file for writing: {$tmp}"]);
            throw new \RuntimeException("Cannot open file for writing: {$tmp}");
        }

        try {
            fwrite($fh, "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\n");
            fwrite($fh, "<BT9000_XML_FILE>\n");
            fwrite($fh, "<BT9000_Version>{$version}</BT9000_Version>\n");
            fwrite($fh, "<Generated_By>{$generatedBy}</Generated_By>\n");
            fwrite($fh, "<Station_ID>{$stationId}</Station_ID>\n");
            fwrite($fh, "<File_Creation_Date>{$fileDate}</File_Creation_Date>\n");
            fwrite($fh, "<Price_Book>\n");

            $this->writeDepartments($fh);
            $this->writePriceGroups($fh);
            $this->writeSkus($fh);
            $this->writeMixAndMatches($fh);
            $this->writeDealGroupSection($fh, 'site', 'Site_Deal_Groups');
            $this->writeDealGroupSection($fh, 'head_office', 'Head_Office_Deal_Groups');
            $this->writeDealGroupSection($fh, 'home_office', 'Home_Office_Deal_Groups');

            fwrite($fh, "<Accounts_Receivable>\n</Accounts_Receivable>\n");

            $this->writePayouts($fh);
            $this->writeLoyaltyCards($fh);

            fwrite($fh, "<Local_Item_Price_File>\n</Local_Item_Price_File>\n");

            $this->writeTendersCoupons($fh);

            fwrite($fh, "<Surcharges>\n</Surcharges>\n");
            fwrite($fh, "</Price_Book>\n");
            fwrite($fh, "</BT9000_XML_FILE>\n");

            fclose($fh);
            rename($tmp, $path);

            $export->update([
                'status'           => 'completed',
                'finished_at'      => now(),
                'records_exported' => DB::table('pb_skus')->count(),
            ]);
        } catch (\Throwable $e) {
            fclose($fh);
            @unlink($tmp);
            $export->update([
                'status'        => 'failed',
                'finished_at'   => now(),
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Section writers
    // -------------------------------------------------------------------------

    /** @param resource $fh */
    private function writeDepartments($fh): void
    {
        fwrite($fh, "<Departments>\n");
        fwrite($fh, "<!--Description -AlphaNumeric max size = 18-->\n");
        fwrite($fh, "<!--Shift_Report_Flag -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Sales_Summary_Report -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--BT9000_Inventory_Control -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Age_Requirements -Numeric '0' - '99'-->\n");
        fwrite($fh, "<!--Default_Item -Numeric  '0000000000000' - '9999999999999'-->\n");
        fwrite($fh, "<!--Conexxus_Product_Code -Numeric 001 - 999-->\n");

        DB::table('pb_departments')->orderBy('department_number')->chunk(500, function ($rows) use ($fh) {
            foreach ($rows as $row) {
                fwrite($fh, "<Department Department_Number=\"{$row->department_number}\">\n");
                fwrite($fh, $this->el('Description', $this->pad18($row->description)));
                fwrite($fh, $this->el('Shift_Report_Flag', $this->yn($row->shift_report_flag)));
                fwrite($fh, $this->el('Sales_Summary_Report', $this->yn($row->sales_summary_report)));
                fwrite($fh, $this->elOpt('Owner', $row->owner));
                fwrite($fh, $this->elOpt('BT9000_Inventory_Control', $this->yn($row->bt9000_inventory_control)));
                fwrite($fh, $this->elOpt('Conexxus_Product_Code', $row->conexxus_product_code));
                fwrite($fh, $this->elOpt('Gift_Card_Department', $this->yn($row->gift_card_department)));
                fwrite($fh, "</Department>\n");
            }
        });

        fwrite($fh, "</Departments>\n");
    }

    /** @param resource $fh */
    private function writePriceGroups($fh): void
    {
        fwrite($fh, "<Price_Groups>\n");
        fwrite($fh, "<!--English_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--French_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--Price -Numeric price format, .00 to 9999.99-->\n");

        $qtyByGroup = DB::table('pb_price_group_quantity_pricing')
            ->orderBy('quantity')
            ->get()
            ->groupBy('price_group_number');

        DB::table('pb_price_groups')->orderBy('price_group_number')->chunk(500, function ($rows) use ($fh, $qtyByGroup) {
            foreach ($rows as $row) {
                fwrite($fh, "<Price_Group Price_Group_Number = \"{$row->price_group_number}\">\n");
                fwrite($fh, $this->el('English_Description', $this->pad18($row->english_description)));
                fwrite($fh, $this->elOpt('French_Description', $row->french_description !== null ? $this->pad18($row->french_description) : null));
                fwrite($fh, $this->el('Price', $this->price($row->price)));

                $qty = $qtyByGroup->get($row->price_group_number, collect());
                fwrite($fh, "<Quantity_Pricing>\n");
                foreach ($qty as $q) {
                    fwrite($fh, "<Local_Quantity_Pricing>\n");
                    fwrite($fh, $this->el('Quantity', (string) $q->quantity));
                    fwrite($fh, $this->el('Price', $this->price($q->price)));
                    fwrite($fh, "</Local_Quantity_Pricing>\n");
                }
                fwrite($fh, "</Quantity_Pricing>\n");

                fwrite($fh, "</Price_Group>\n");
            }
        });

        fwrite($fh, "</Price_Groups>\n");
    }

    /** @param resource $fh */
    private function writeSkus($fh): void
    {
        fwrite($fh, "<Stock_Keeping_Units>\n");
        fwrite($fh, "<!--English_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--French_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--Department -Numeric, '1' - '999999'-->\n");
        fwrite($fh, "<!--Price -Numeric, '0.01' - '9999.99'-->\n");
        fwrite($fh, "<!--Item_Deposit -Numeric, '0.01 - 9999.99'-->\n");
        fwrite($fh, "<!--Promo_Code -AlphaNumeric, max size = 12-->\n");
        fwrite($fh, "<!--Host_Product_Code -Numeric type '0' - '999'-->\n");
        fwrite($fh, "<!--TAX1 or GST_On_Item -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--TAX2 or PST_On_Item -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--TAX3 -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--TAX4 -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--TAX5 -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--TAX6 -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--TAX7 -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--TAX8 -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Prompt_For_Price -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Item_Not_Active -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Tax_Included_Price -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Price_Group -Numeric '0000000000000'-'9999999999999' or blank filled-->\n");
        fwrite($fh, "<!--Wash_Type -AlphaNumeric, 1 character, '1' = PRIMARY, '3' = PRINT BOTH, '5' = VOUCHER ONLY-->\n");
        fwrite($fh, "<!--Car_Wash_Controller_Code -Numeric, '0' - '9999999'-->\n");
        fwrite($fh, "<!--Upsell_Quantity_For_Car_Wash_At_Pump -Numeric, '0' - '99'-->\n");
        fwrite($fh, "<!--Petro_Canada_PASS_Code -Numeric, '0' - '99'-->\n");
        fwrite($fh, "<!--Item_Desc_Not_On_2nd_Monitor -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Ontario_RST_Tax_Off -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Ontario_RST_Tax_On -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Federal_Baked_Good_Item -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Prevent_BT9000_Inventory_Control -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Conexxus_Product_Code -Numeric, '000' - '999'-->\n");
        fwrite($fh, "<!--Car_Wash_Expiry_In_Days -Numeric, '0' - '365'-->\n");
        fwrite($fh, "<!--AFD_Car_Wash_Position_On_Screen -Numeric, '0' - '99'-->\n");
        fwrite($fh, "<!--Upsell_Quantity_For_Car_Wash_At_Pump -Numeric, '0' - '99'-->\n");
        fwrite($fh, "<!--Petro_Canada_PASS_Code -Numeric, '0' - '99'-->\n");
        fwrite($fh, "<!--Age_Requirements -Numeric '0' - '99'-->\n");
        fwrite($fh, "<!--Redemption_Only -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Time_Restrictions -All Logical fields in section, 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Section 'Quantity_Pricing'-->\n");
        fwrite($fh, "<!--Quantity -Numeric '0' - '999'-->\n");
        fwrite($fh, "<!--Price -Numeric '0.01' - '9999.99'-->\n");
        fwrite($fh, "<!--Section 'Linked_SKUs'-->\n");
        fwrite($fh, "<!--Linked_SKU -Numeric '0000000000000' - '9999999999999'-->\n");
        fwrite($fh, "<!--Section 'Linkable_SKUs'-->\n");
        fwrite($fh, "<!--Linkable_SKU -Numeric '0000000000000' - '9999999999999'-->\n");
        fwrite($fh, "<!--Section 'UPCs'-->\n");
        fwrite($fh, "<!--UPC -Numeric '0000000000000' - '9999999999999'-->\n");

        // Pre-load all child data keyed by item_number
        $upcsByItem    = DB::table('pb_sku_upcs')->orderBy('id')->get()->groupBy('item_number');
        $qtyByItem     = DB::table('pb_sku_quantity_pricing')->orderBy('id')->get()->groupBy('item_number');
        $linkedByItem  = DB::table('pb_sku_linked_skus')->orderBy('id')->get()->groupBy('item_number');
        $linkableByItem = DB::table('pb_sku_linkable_skus')->orderBy('id')->get()->groupBy('item_number');

        DB::table('pb_skus')->orderBy('item_number')->chunk(500, function ($rows) use ($fh, $upcsByItem, $qtyByItem, $linkedByItem, $linkableByItem) {
            foreach ($rows as $row) {
                $n = $row->item_number;
                fwrite($fh, "<Stock_Keeping_Unit Item_Number = \"{$n}\">\n");
                fwrite($fh, $this->el('Price', $this->price($row->price)));
                fwrite($fh, $this->el('English_Description', $this->pad18($row->english_description)));
                fwrite($fh, $this->elOpt('French_Description', $row->french_description !== null ? $this->pad18($row->french_description) : null));
                fwrite($fh, $this->el('Department', $row->department_number));
                fwrite($fh, $this->elOpt('Price_Group', $row->price_group_number));
                fwrite($fh, $this->elOpt('Item_Deposit', $this->price($row->item_deposit)));
                fwrite($fh, $this->elOpt('Promo_Code', $row->promo_code));
                fwrite($fh, $this->elOpt('Host_Product_Code', $row->host_product_code));
                fwrite($fh, $this->elOpt('TAX1', $this->yn($row->tax1)));
                fwrite($fh, $this->elOpt('TAX2', $this->yn($row->tax2)));
                fwrite($fh, $this->elOpt('TAX3', $this->yn($row->tax3)));
                fwrite($fh, $this->elOpt('TAX4', $this->yn($row->tax4)));
                fwrite($fh, $this->elOpt('TAX5', $this->yn($row->tax5)));
                fwrite($fh, $this->elOpt('TAX6', $this->yn($row->tax6)));
                fwrite($fh, $this->elOpt('TAX7', $this->yn($row->tax7)));
                fwrite($fh, $this->elOpt('TAX8', $this->yn($row->tax8)));
                fwrite($fh, $this->elOpt('Prompt_For_Price', $this->yn($row->prompt_for_price)));
                fwrite($fh, $this->elOpt('Item_Not_Active', $this->yn($row->item_not_active)));
                fwrite($fh, $this->elOpt('Tax_Included_Price', $this->yn($row->tax_included_price)));
                fwrite($fh, $this->elOpt('Wash_Type', $row->wash_type));
                fwrite($fh, $this->elOpt('Car_Wash_Controller_Code', $row->car_wash_controller_code !== null ? (string) $row->car_wash_controller_code : null));
                fwrite($fh, $this->elOpt('Upsell_Quantity_For_Car_Wash_At_Pump', $row->upsell_qty_car_wash !== null ? (string) $row->upsell_qty_car_wash : null));
                fwrite($fh, $this->elOpt('Petro_Canada_PASS_Code', $row->petro_canada_pass_code !== null ? (string) $row->petro_canada_pass_code : null));
                fwrite($fh, $this->elOpt('Item_Desc_Not_On_2nd_Monitor', $this->yn($row->item_desc_not_on_2nd_monitor)));
                fwrite($fh, $this->elOpt('Ontario_RST_Tax_Off', $this->yn($row->ontario_rst_tax_off)));
                fwrite($fh, $this->elOpt('Ontario_RST_Tax_On', $this->yn($row->ontario_rst_tax_on)));
                fwrite($fh, $this->elOpt('Federal_Baked_Good_Item', $this->yn($row->federal_baked_good_item)));
                fwrite($fh, $this->elOpt('Prevent_BT9000_Inventory_Control', $this->yn($row->prevent_bt9000_inventory_control)));
                fwrite($fh, $this->elOpt('Conexxus_Product_Code', $row->conexxus_product_code));
                fwrite($fh, $this->elOpt('Car_Wash_Expiry_In_Days', $row->car_wash_expiry_in_days !== null ? (string) $row->car_wash_expiry_in_days : null));
                fwrite($fh, $this->elOpt('AFD_Car_Wash_Position_On_Screen', $row->afd_car_wash_position !== null ? (string) $row->afd_car_wash_position : null));
                fwrite($fh, $this->elOpt('Age_Requirements', $row->age_requirements !== null ? (string) $row->age_requirements : null));
                fwrite($fh, $this->elOpt('Redemption_Only', $this->yn($row->redemption_only)));
                fwrite($fh, $this->el('Loyalty_Card_Eligible', $row->loyalty_card_eligible ? 'Y' : 'N'));
                fwrite($fh, $this->elOpt('Delivery_Channel_Price', $this->price($row->delivery_channel_price)));
                fwrite($fh, $this->elOpt('Tax_Strategy_ID_From_NACS', $row->tax_strategy_id_from_nacs));
                fwrite($fh, $this->elOpt('Owner', $row->owner));

                // UPCs
                $upcs = $upcsByItem->get($n, collect());
                if ($upcs->isNotEmpty()) {
                    fwrite($fh, "<UPCs>\n");
                    foreach ($upcs as $u) {
                        fwrite($fh, $this->el('UPC', $u->upc));
                    }
                    fwrite($fh, "</UPCs>\n");
                }

                // Quantity Pricing
                $qty = $qtyByItem->get($n, collect());
                if ($qty->isNotEmpty()) {
                    fwrite($fh, "<Quantity_Pricing>\n");
                    foreach ($qty as $q) {
                        fwrite($fh, "<Local_Quantity_Pricing>\n");
                        fwrite($fh, $this->el('Quantity', (string) $q->quantity));
                        fwrite($fh, $this->el('Price', $this->price($q->price)));
                        fwrite($fh, "</Local_Quantity_Pricing>\n");
                    }
                    fwrite($fh, "</Quantity_Pricing>\n");
                }

                // Linked SKUs
                $linked = $linkedByItem->get($n, collect());
                if ($linked->isNotEmpty()) {
                    fwrite($fh, "<Linked_SKUs>\n");
                    foreach ($linked as $l) {
                        fwrite($fh, $this->el('Linked_SKU', $l->linked_item_number));
                    }
                    fwrite($fh, "</Linked_SKUs>\n");
                }

                // Linkable SKUs
                $linkable = $linkableByItem->get($n, collect());
                if ($linkable->isNotEmpty()) {
                    fwrite($fh, "<Linkable_SKUs>\n");
                    foreach ($linkable as $l) {
                        fwrite($fh, $this->el('Linkable_SKU', $l->linkable_item_number));
                    }
                    fwrite($fh, "</Linkable_SKUs>\n");
                }

                fwrite($fh, "</Stock_Keeping_Unit>\n");
            }
        });

        fwrite($fh, "</Stock_Keeping_Units>\n");
    }

    /** @param resource $fh */
    private function writeMixAndMatches($fh): void
    {
        fwrite($fh, "<Mix_And_Matches>\n");

        $membersByGroup = DB::table('pb_mix_and_match_members')
            ->orderBy('id')
            ->get()
            ->groupBy('mix_and_match_identifier');

        DB::table('pb_mix_and_matches')->orderBy('mix_and_match_identifier')->chunk(500, function ($rows) use ($fh, $membersByGroup) {
            foreach ($rows as $row) {
                $id = $row->mix_and_match_identifier;
                fwrite($fh, "<Mix_And_Match Mix_And_Match_Identifier = \"{$id}\">\n");

                if ($row->english_description !== null) {
                    fwrite($fh, "<Description lang=\"English\">" . $this->esc($row->english_description) . "</Description>\n");
                }
                fwrite($fh, "<Description lang=\"French\">" . $this->esc($row->french_description ?? '') . "</Description>\n");

                $members = $membersByGroup->get($id, collect());
                fwrite($fh, "<Mix_And_Match_Members_Of_Group>\n");
                foreach ($members as $m) {
                    fwrite($fh, $this->el('Stock_Keeping_Unit', $m->item_number));
                }
                fwrite($fh, "</Mix_And_Match_Members_Of_Group>\n");

                fwrite($fh, "</Mix_And_Match>\n");
            }
        });

        fwrite($fh, "</Mix_And_Matches>\n");
    }

    /** @param resource $fh */
    private function writeDealGroupSection($fh, string $type, string $tagName): void
    {
        fwrite($fh, "<{$tagName}>\n");

        if ($type === 'site') {
            fwrite($fh, "<!--English_Description -AlphaNumeric, max size = 18-->\n");
            fwrite($fh, "<!--French_Description -AlphaNumeric, max size = 18-->\n");
            fwrite($fh, "<!--English_AFD_Car_Wash_Message_For_Gilbarco -AlphaNumeric, max size = 80-->\n");
            fwrite($fh, "<!--French_AFD_Car_Wash_Message_For_Gilbarco -AlphaNumeric, max size = 80-->\n");
            fwrite($fh, "<!--Fuel_Mix_And_Match_Check -Logical field, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--Dont_Calculate_Deal -Logical field, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--Deal_Not_Active -Logical field, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--Available_In_Kiosk_Only -Logical field, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--CPL_STACKING_CPN -Logical field, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--Available_At_Pump_Only -Logical field, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--Reason_Code_For_Deal -Numeric field, '0' - '2-->\n");
            fwrite($fh, "<!--Station_ID_For_Deal -AlphaNumeric max size = 7-->\n");
            fwrite($fh, "<!--<Requires_Fuel_To_Complete_Deal>--><!--Required_Fuel_POS_Grade -Numeric, '1' - '99' or 'Some fuel - any type'-->\n");
            fwrite($fh, "<!--Required_Fuel_Litres -Numeric, '1' - '999'-->\n");
            fwrite($fh, "<!--<Loyalty_Card_Required_To_Use_Deal_Group>-->\n");
            fwrite($fh, "<!--Loyalty_Card_Description -AlphaNumeric, max size = 18-->\n");
            fwrite($fh, "<!--Card_Restriction -Logical field, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--Card_Swipe_Type -Numeric, '1' - '99'-->\n");
            fwrite($fh, "<!--<CPL_Fuel_Discounting>-->\n");
            fwrite($fh, "<!--POS_Grade -Numeric, '1' - '99'-->\n");
            fwrite($fh, "<!--CPL_Discount_On_Fuel -Numeric, '0.1' - '999.9'-->\n");
            fwrite($fh, "<!--Section <Time_Restrictions> ALL LOGICAL FIELDS, 'Y' or 'N'-->\n");
            fwrite($fh, "<!--Fixed_Dollar_Off -Numeric, '.01' - '99.99'-->\n");
            fwrite($fh, "<!--Max_Per_Customer -Numeric, '1' - '999'-->\n");
            fwrite($fh, "<!--Start_Date -Numeric 'YYYYMMDD' -->\n");
            fwrite($fh, "<!--End_Date -Numeric 'YYYYMMDD' -->\n");
            fwrite($fh, "<!--Section <Deal_Group_Component>-->\n");
            fwrite($fh, "<!--Quantity -Numeric, '1' - '999'-->\n");
            fwrite($fh, "<!--'Item' or 'Price_Group' or 'Mix_And_Match'-->\n");
            fwrite($fh, "<!--Numeric, Item ID '0000000000000' - '9999999999999'-->\n");
            fwrite($fh, "<!--Price_For_Quantity_One -Numeric, '.01' - '99999.99'-->\n");
            fwrite($fh, "<!--Percentage_Off -Numeric, '1' - '99'-->\n");
            fwrite($fh, "<!--Amount_Off -Numeric, '.01' - '99999.99'-->\n");
            fwrite($fh, "<!--Section <UPCs>-->\n");
            fwrite($fh, "<!--UPC -Numeric, Item ID '0000000000000' - '9999999999999'-->\n");
            fwrite($fh, "<!--Coupon_Accounting_Implications -Numeric, '000000' - '999999'-->\n");
        }

        $groups = DB::table('pb_deal_groups')
            ->where('type', $type)
            ->orderBy('deal_group_number')
            ->get();

        if ($groups->isEmpty()) {
            fwrite($fh, "</{$tagName}>\n");
            return;
        }

        $groupIds = $groups->pluck('id');

        $cplByGroup = DB::table('pb_deal_group_cpl_fuel_discounts')
            ->whereIn('deal_group_id', $groupIds)
            ->orderBy('id')
            ->get()
            ->groupBy('deal_group_id');

        $componentsByGroup = DB::table('pb_deal_group_components')
            ->whereIn('deal_group_id', $groupIds)
            ->orderBy('id')
            ->get()
            ->groupBy('deal_group_id');

        $allComponentIds = $componentsByGroup->flatten()->pluck('id');
        $upcsByComponent = $allComponentIds->isEmpty()
            ? collect()
            : DB::table('pb_deal_group_component_upcs')
                ->whereIn('deal_group_component_id', $allComponentIds)
                ->orderBy('id')
                ->get()
                ->groupBy('deal_group_component_id');

        foreach ($groups as $group) {
            fwrite($fh, "<Deal_Group Deal_Group_Number = \"{$group->deal_group_number}\">\n");
            fwrite($fh, $this->elOpt('English_Description', $group->english_description !== null ? $this->pad18($group->english_description) : null));
            fwrite($fh, $this->elOpt('French_Description', $group->french_description !== null ? $this->pad18($group->french_description) : null));
            fwrite($fh, $this->elOpt('Start_Date', $group->start_date !== null ? str_replace('-', '', $group->start_date) : null));
            fwrite($fh, $this->elOpt('End_Date', $group->end_date !== null ? str_replace('-', '', $group->end_date) : null));
            fwrite($fh, $this->elOpt('Fuel_Mix_And_Match_Check', $this->yn($group->fuel_mix_and_match_check)));
            fwrite($fh, $this->elOpt('Dont_Calculate_Deal', $this->yn($group->dont_calculate_deal)));
            fwrite($fh, $this->elOpt('Deal_Not_Active', $this->yn($group->deal_not_active)));
            fwrite($fh, $this->elOpt('Available_In_Kiosk_Only', $this->yn($group->available_in_kiosk_only)));
            fwrite($fh, $this->elOpt('CPL_STACKING_CPN', $this->yn($group->cpl_stacking_cpn)));
            fwrite($fh, $this->elOpt('Available_At_Pump_Only', $this->yn($group->available_at_pump_only)));
            fwrite($fh, $this->elOpt('Reason_Code_For_Deal', $group->reason_code_for_deal !== null ? (string) $group->reason_code_for_deal : null));
            fwrite($fh, $this->elOpt('Station_ID_For_Deal', $group->station_id_for_deal));
            fwrite($fh, $this->elOpt('Fixed_Dollar_Off', $this->price($group->fixed_dollar_off)));
            fwrite($fh, $this->elOpt('Max_Per_Customer', $group->max_per_customer !== null ? (string) $group->max_per_customer : null));

            if ($group->req_fuel_pos_grade !== null || $group->req_fuel_litres !== null) {
                fwrite($fh, "<Requires_Fuel_To_Complete_Deal>\n");
                fwrite($fh, $this->elOpt('Required_Fuel_POS_Grade', $group->req_fuel_pos_grade));
                fwrite($fh, $this->elOpt('Required_Fuel_Litres', $group->req_fuel_litres !== null ? (string) $group->req_fuel_litres : null));
                fwrite($fh, "</Requires_Fuel_To_Complete_Deal>\n");
            }

            if ($group->loyalty_card_description !== null) {
                fwrite($fh, "<Loyalty_Card_Required_To_Use_Deal_Group>\n");
                fwrite($fh, $this->el('Loyalty_Card_Description', $group->loyalty_card_description));
                fwrite($fh, $this->elOpt('Card_Restriction', $this->yn($group->loyalty_card_restriction)));
                fwrite($fh, $this->elOpt('Card_Swipe_Type', $group->loyalty_card_swipe_type !== null ? (string) $group->loyalty_card_swipe_type : null));
                fwrite($fh, "</Loyalty_Card_Required_To_Use_Deal_Group>\n");
            }

            // CPL Fuel Discounts — group by discount value, list POS grades under each
            $cpls = $cplByGroup->get($group->id, collect());
            if ($cpls->isNotEmpty()) {
                $byDiscount = $cpls->groupBy('cpl_discount_on_fuel');
                foreach ($byDiscount as $discount => $grades) {
                    fwrite($fh, "<CPL_Fuel_Discounting>\n");
                    fwrite($fh, $this->el('CPL_Discount_On_Fuel', (string) $discount));
                    foreach ($grades as $g) {
                        fwrite($fh, $this->el('POS_Grade', $g->pos_grade));
                    }
                    fwrite($fh, "</CPL_Fuel_Discounting>\n");
                }
            }

            // Components
            $components = $componentsByGroup->get($group->id, collect());
            fwrite($fh, "<Deal_Group_Components>\n");
            foreach ($components as $comp) {
                fwrite($fh, "<Deal_Group_Component>\n");
                fwrite($fh, $this->elOpt('Item', $comp->item_number));
                fwrite($fh, $this->elOpt('Price_Group', $comp->price_group_number));
                fwrite($fh, $this->elOpt('Mix_And_Match', $comp->mix_and_match_identifier));
                fwrite($fh, $this->el('Quantity', (string) $comp->quantity));
                fwrite($fh, $this->el('Price_For_Quantity_One', $this->price($comp->price_for_quantity_one)));
                fwrite($fh, $this->elOpt('Percentage_Off', $comp->percentage_off !== null ? (string) $comp->percentage_off : null));
                fwrite($fh, $this->elOpt('Amount_Off', $this->price($comp->amount_off)));
                fwrite($fh, $this->elOpt('Coupon_Accounting_Implications', $comp->coupon_accounting_implications));

                $compUpcs = $upcsByComponent->get($comp->id, collect());
                if ($compUpcs->isNotEmpty()) {
                    fwrite($fh, "<UPCs>\n");
                    foreach ($compUpcs as $u) {
                        fwrite($fh, $this->el('UPC', $u->upc));
                    }
                    fwrite($fh, "</UPCs>\n");
                }

                fwrite($fh, "</Deal_Group_Component>\n");
            }
            fwrite($fh, "</Deal_Group_Components>\n");

            fwrite($fh, "</Deal_Group>\n");
        }

        fwrite($fh, "</{$tagName}>\n");
    }

    /** @param resource $fh */
    private function writePayouts($fh): void
    {
        fwrite($fh, "<Payouts>\n");
        fwrite($fh, "<!--English_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--French_Description -AlphaNumeric, max size = 18-->\n");

        DB::table('pb_payouts')->orderBy('payout_number')->chunk(500, function ($rows) use ($fh) {
            foreach ($rows as $row) {
                fwrite($fh, "<Payout Payout_Number = \"{$row->payout_number}\">\n");
                fwrite($fh, $this->el('English_Description', $this->pad18($row->english_description)));
                fwrite($fh, $this->elOpt('French_Description', $row->french_description !== null ? $this->pad18($row->french_description) : null));
                fwrite($fh, "</Payout>\n");
            }
        });

        fwrite($fh, "</Payouts>\n");
    }

    /** @param resource $fh */
    private function writeLoyaltyCards($fh): void
    {
        fwrite($fh, "<Loyalty_Card_Definitions>\n");

        $binsByCard = DB::table('pb_loyalty_card_bins')
            ->orderBy('id')
            ->get()
            ->groupBy('loyalty_card_id');

        DB::table('pb_loyalty_cards')->orderBy('id')->chunk(500, function ($rows) use ($fh, $binsByCard) {
            foreach ($rows as $row) {
                fwrite($fh, "<Loyalty_Card>\n");
                fwrite($fh, $this->el('Card_Name', $row->card_name));

                $bins = $binsByCard->get($row->id, collect());
                foreach ($bins as $bin) {
                    fwrite($fh, "<Bins>\n");
                    fwrite($fh, $this->el('Start_ISO_Bin', $bin->start_iso_bin));
                    fwrite($fh, $this->el('End_ISO_Bin', $bin->end_iso_bin));
                    fwrite($fh, $this->el('Min_Length', (string) $bin->min_length));
                    fwrite($fh, $this->el('Max_Length', (string) $bin->max_length));
                    fwrite($fh, $this->el('Check_Digit', (string) $bin->check_digit));
                    fwrite($fh, "</Bins>\n");
                }

                fwrite($fh, "</Loyalty_Card>\n");
            }
        });

        fwrite($fh, "</Loyalty_Card_Definitions>\n");
    }

    /** @param resource $fh */
    private function writeTendersCoupons($fh): void
    {
        fwrite($fh, "<Tenders_Coupons>\n");
        fwrite($fh, "<!--English_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--French_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--Prompt_For_Amount -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Tender_Type -Numeric 0 - 1-->\n");
        fwrite($fh, "<!--Amount -Numeric 0.01 - 999.99-->\n");
        fwrite($fh, "<!--<Loyalty_Card_Required_To_Use_Deal_Group>-->\n");
        fwrite($fh, "<!--Loyalty_Card_Description -AlphaNumeric, max size = 18-->\n");
        fwrite($fh, "<!--Type_Of_Restrictions -Numeric -->\n");
        fwrite($fh, "<!--Restriction_Identifier -Numeric -->\n");
        fwrite($fh, "<!--Available_At_Pump_Only -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Available_In_Kiosk_Only -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Coupon_Not_Active -Logical 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Max_Per_Customer -Numeric, '1' - '999'-->\n");
        fwrite($fh, "<!--Time_Restrictions -All Logical fields in section, 'Y' or 'N'-->\n");
        fwrite($fh, "<!--Start_Date -Numeric 'YYYYMMDD' -->\n");
        fwrite($fh, "<!--End_Date -Numeric 'YYYYMMDD' -->\n");
        fwrite($fh, "<!--Coupon_Accounting_Implications -Numeric, '000000' - '999999'-->\n");
        fwrite($fh, "<!--Section <UPCs>-->\n");
        fwrite($fh, "<!--UPC -Numeric, Item ID '0000000000000' - '9999999999999'-->\n");

        DB::table('pb_tenders_coupons')->orderBy('item_number')->chunk(500, function ($rows) use ($fh) {
            foreach ($rows as $row) {
                fwrite($fh, "<Item Item_Number = \"{$row->item_number}\">\n");
                fwrite($fh, $this->el('English_Description', $this->pad18($row->english_description)));
                fwrite($fh, $this->elOpt('French_Description', $row->french_description !== null ? $this->pad18($row->french_description) : null));
                fwrite($fh, "</Item>\n");
            }
        });

        fwrite($fh, "</Tenders_Coupons>\n");
    }

    // -------------------------------------------------------------------------
    // XML helpers
    // -------------------------------------------------------------------------

    private function el(string $tag, ?string $value): string
    {
        return "<{$tag}>" . $this->esc($value ?? '') . "</{$tag}>\n";
    }

    private function elOpt(string $tag, ?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return "<{$tag}>" . $this->esc($value) . "</{$tag}>\n";
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function pad18(?string $value): string
    {
        return str_pad($value ?? '', 18);
    }

    private function price(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return number_format((float) $value, 2, '.', '');
    }

    private function yn(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value ? 'Y' : 'N';
    }
}
