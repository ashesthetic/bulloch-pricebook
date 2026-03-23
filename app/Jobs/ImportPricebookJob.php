<?php

namespace App\Jobs;

use App\Models\Pricebook\PricebookImport;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use XMLReader;

class ImportPricebookJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        private readonly string $filePath,
        private readonly int $importId,
    ) {}

    public function handle(): void
    {
        $import = PricebookImport::findOrFail($this->importId);

        try {
            // Estimate total records for progress
            $totalRecords = $this->estimateTotalRecords($this->filePath);
            $import->update(['total_records' => $totalRecords]);

            // Truncate all pricebook data tables
            $this->truncateTables();

            // Stream and import
            ['counts' => $counts, 'meta' => $meta] = $this->streamImport($this->filePath, $import);

            $import->update([
                'status'             => 'completed',
                'finished_at'        => now(),
                'records_imported'   => $counts,
                'progress_percentage' => 100,
                'current_section'    => null,
                'bt9000_version'     => $meta['BT9000_Version'] ?? null,
                'generated_by'       => $meta['Generated_By'] ?? null,
                'station_id'         => isset($meta['Station_ID']) && $meta['Station_ID'] !== '' ? (int) $meta['Station_ID'] : null,
                'file_creation_date' => $meta['File_Creation_Date'] ?? null,
                'file_created_at'    => isset($meta['File_Creation_Date']) && $meta['File_Creation_Date'] !== ''
                    ? \Carbon\Carbon::createFromFormat('YmdHi', $meta['File_Creation_Date'])->toDateTimeString()
                    : null,
            ]);
        } catch (\Throwable $e) {
            $import->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function estimateTotalRecords(string $filePath): int
    {
        // Count Stock_Keeping_Unit elements as the majority of records
        $output = shell_exec('grep -c "<Stock_Keeping_Unit " ' . escapeshellarg($filePath) . ' 2>/dev/null');
        $skuCount = (int) trim((string) $output);

        // Add rough estimate for other sections (~500 additional records)
        return max($skuCount + 500, 1);
    }

    private function truncateTables(): void
    {
        $tables = [
            'pb_tenders_coupons',
            'pb_loyalty_card_bins',
            'pb_loyalty_cards',
            'pb_payouts',
            'pb_deal_group_component_upcs',
            'pb_deal_group_components',
            'pb_deal_group_cpl_fuel_discounts',
            'pb_deal_groups',
            'pb_mix_and_match_members',
            'pb_mix_and_matches',
            'pb_sku_linkable_skus',
            'pb_sku_linked_skus',
            'pb_sku_quantity_pricing',
            'pb_sku_upcs',
            'pb_skus',
            'pb_price_group_quantity_pricing',
            'pb_price_groups',
            'pb_departments',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
    }

    private function streamImport(string $filePath, PricebookImport $import): array
    {
        $reader = new XMLReader();
        if (! $reader->open($filePath)) {
            throw new \RuntimeException("Cannot open XML file: {$filePath}");
        }

        // DOMDocument used as context for XMLReader::expand()
        $dom = new \DOMDocument();

        $counts = [];
        $currentSection = null;
        $processedRecords = 0;
        $headerMeta = [];

        // Buffers for batch insert (keyed by table name)
        $buffers = [];
        $batchSize = 500;

        $now = now()->toDateTimeString();

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            $name = $reader->localName;

            // Capture file-level header metadata
            if (in_array($name, ['BT9000_Version', 'Generated_By', 'Station_ID', 'File_Creation_Date']) && $currentSection === null) {
                $reader->read(); // move to text node
                $headerMeta[$name] = $reader->value;
                continue;
            }

            // Track which section we're in
            if (in_array($name, [
                'Departments', 'Price_Groups', 'Stock_Keeping_Units',
                'Mix_And_Matches', 'Site_Deal_Groups', 'Head_Office_Deal_Groups',
                'Home_Office_Deal_Groups', 'Payouts', 'Loyalty_Card_Definitions',
                'Tenders_Coupons',
            ])) {
                $currentSection = $name;

                if ($currentSection !== null) {
                    $import->update(['current_section' => $currentSection]);
                }
                continue;
            }

            // Route record elements to section parsers
            if ($name === 'Department' && $currentSection === 'Departments') {
                $node = simplexml_import_dom($reader->expand($dom));
                $buffers['pb_departments'][] = $this->parseDepartment($node, $now);
                $counts['departments'] = ($counts['departments'] ?? 0) + 1;
                $processedRecords++;
                $this->maybeFlush($buffers, 'pb_departments', $batchSize, $processedRecords, $import);

            } elseif ($name === 'Price_Group' && $currentSection === 'Price_Groups') {
                $node = simplexml_import_dom($reader->expand($dom));
                ['row' => $row, 'qty' => $qty] = $this->parsePriceGroup($node, $now);
                $buffers['pb_price_groups'][] = $row;
                foreach ($qty as $q) {
                    $buffers['pb_price_group_quantity_pricing'][] = $q;
                }
                $counts['price_groups'] = ($counts['price_groups'] ?? 0) + 1;
                $processedRecords++;
                $this->maybeFlush($buffers, 'pb_price_groups', $batchSize, $processedRecords, $import);
                $this->maybeFlush($buffers, 'pb_price_group_quantity_pricing', $batchSize, $processedRecords, $import);

            } elseif ($name === 'Stock_Keeping_Unit' && $currentSection === 'Stock_Keeping_Units') {
                $node = simplexml_import_dom($reader->expand($dom));
                $parsed = $this->parseSku($node, $now);
                $buffers['pb_skus'][] = $parsed['sku'];
                foreach ($parsed['upcs'] as $u) {
                    $buffers['pb_sku_upcs'][] = $u;
                }
                foreach ($parsed['qtyPricing'] as $q) {
                    $buffers['pb_sku_quantity_pricing'][] = $q;
                }
                foreach ($parsed['linked'] as $l) {
                    $buffers['pb_sku_linked_skus'][] = $l;
                }
                foreach ($parsed['linkable'] as $l) {
                    $buffers['pb_sku_linkable_skus'][] = $l;
                }
                $counts['skus'] = ($counts['skus'] ?? 0) + 1;
                $processedRecords++;
                $this->maybeFlush($buffers, 'pb_skus', $batchSize, $processedRecords, $import);
                $this->maybeFlush($buffers, 'pb_sku_upcs', $batchSize, $processedRecords, $import);
                $this->maybeFlush($buffers, 'pb_sku_quantity_pricing', $batchSize, $processedRecords, $import);
                $this->maybeFlush($buffers, 'pb_sku_linked_skus', $batchSize, $processedRecords, $import);
                $this->maybeFlush($buffers, 'pb_sku_linkable_skus', $batchSize, $processedRecords, $import);

            } elseif ($name === 'Mix_And_Match' && $currentSection === 'Mix_And_Matches') {
                $node = simplexml_import_dom($reader->expand($dom));
                ['row' => $row, 'members' => $members] = $this->parseMixAndMatch($node, $now);
                $buffers['pb_mix_and_matches'][] = $row;
                foreach ($members as $m) {
                    $buffers['pb_mix_and_match_members'][] = $m;
                }
                $counts['mix_and_matches'] = ($counts['mix_and_matches'] ?? 0) + 1;
                $processedRecords++;
                $this->maybeFlush($buffers, 'pb_mix_and_matches', $batchSize, $processedRecords, $import);
                $this->maybeFlush($buffers, 'pb_mix_and_match_members', $batchSize, $processedRecords, $import);

            } elseif ($name === 'Deal_Group' && in_array($currentSection, ['Site_Deal_Groups', 'Head_Office_Deal_Groups', 'Home_Office_Deal_Groups'])) {
                $node = simplexml_import_dom($reader->expand($dom));
                $type = match ($currentSection) {
                    'Head_Office_Deal_Groups' => 'head_office',
                    'Home_Office_Deal_Groups' => 'home_office',
                    default => 'site',
                };
                $this->parseDealGroup($node, $type, $now);
                $counts['deal_groups'] = ($counts['deal_groups'] ?? 0) + 1;
                $processedRecords++;

            } elseif ($name === 'Payout' && $currentSection === 'Payouts') {
                $node = simplexml_import_dom($reader->expand($dom));
                $buffers['pb_payouts'][] = $this->parsePayout($node, $now);
                $counts['payouts'] = ($counts['payouts'] ?? 0) + 1;
                $processedRecords++;
                $this->maybeFlush($buffers, 'pb_payouts', $batchSize, $processedRecords, $import);

            } elseif ($name === 'Loyalty_Card' && $currentSection === 'Loyalty_Card_Definitions') {
                $node = simplexml_import_dom($reader->expand($dom));
                $this->parseLoyaltyCard($node, $now);
                $counts['loyalty_cards'] = ($counts['loyalty_cards'] ?? 0) + 1;
                $processedRecords++;

            } elseif ($name === 'Item' && $currentSection === 'Tenders_Coupons') {
                $node = simplexml_import_dom($reader->expand($dom));
                $buffers['pb_tenders_coupons'][] = $this->parseTenderCoupon($node, $now);
                $counts['tenders_coupons'] = ($counts['tenders_coupons'] ?? 0) + 1;
                $processedRecords++;
                $this->maybeFlush($buffers, 'pb_tenders_coupons', $batchSize, $processedRecords, $import);
            }
        }

        $reader->close();

        // Flush remaining buffers
        foreach ($buffers as $table => $rows) {
            if (! empty($rows)) {
                $this->insertIgnoreDuplicates($table, $rows);
            }
        }

        return ['counts' => $counts, 'meta' => $headerMeta];
    }

    private function maybeFlush(
        array &$buffers,
        string $table,
        int $batchSize,
        int $processedRecords,
        PricebookImport $import,
    ): void {
        if (isset($buffers[$table]) && count($buffers[$table]) >= $batchSize) {
            $this->insertIgnoreDuplicates($table, $buffers[$table]);
            $buffers[$table] = [];

            // Update progress
            $total = $import->total_records ?: 1;
            $pct = min(99, (int) floor($processedRecords / $total * 100));
            $import->update(['processed_records' => $processedRecords, 'progress_percentage' => $pct]);
        }
    }

    private function insertIgnoreDuplicates(string $table, array $rows): void
    {
        // For pb_sku_upcs, duplicates are possible — skip them gracefully
        if ($table === 'pb_sku_upcs') {
            foreach ($rows as $row) {
                try {
                    DB::table($table)->insert($row);
                } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                    // Duplicate UPC — silently skip
                }
            }
            return;
        }

        DB::table($table)->insert($rows);
    }

    // -------------------------------------------------------------------------
    // Section parsers
    // -------------------------------------------------------------------------

    private function parseDepartment(\SimpleXMLElement $node, string $now): array
    {
        return [
            'department_number'         => (string) $node['Department_Number'],
            'description'               => (string) $node->Description,
            'shift_report_flag'         => $this->ynRequired((string) $node->Shift_Report_Flag),
            'sales_summary_report'      => $this->ynRequired((string) $node->Sales_Summary_Report),
            'owner'                     => $this->str($node->Owner),
            'bt9000_inventory_control'  => $this->yn($node->BT9000_Inventory_Control),
            'conexxus_product_code'     => $this->str($node->Conexxus_Product_Code),
            'gift_card_department'      => $this->yn($node->Gift_Card_Department),
            'created_at'                => $now,
            'updated_at'                => $now,
        ];
    }

    private function parsePriceGroup(\SimpleXMLElement $node, string $now): array
    {
        $pgNumber = (string) $node['Price_Group_Number'];

        $row = [
            'price_group_number'  => $pgNumber,
            'english_description' => (string) $node->English_Description,
            'french_description'  => $this->str($node->French_Description),
            'price'               => (float) $node->Price,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $qty = [];
        if (isset($node->Quantity_Pricing->Local_Quantity_Pricing)) {
            foreach ($node->Quantity_Pricing->Local_Quantity_Pricing as $lqp) {
                $qty[] = [
                    'price_group_number' => $pgNumber,
                    'quantity'           => (int) $lqp->Quantity,
                    'price'              => (float) $lqp->Price,
                    'created_at'         => $now,
                    'updated_at'         => $now,
                ];
            }
        }

        return ['row' => $row, 'qty' => $qty];
    }

    private function parseSku(\SimpleXMLElement $node, string $now): array
    {
        $itemNumber = (string) $node['Item_Number'];

        $sku = [
            'item_number'                       => $itemNumber,
            'english_description'               => (string) $node->English_Description,
            'french_description'                => $this->rawStr($node->French_Description),
            'price'                             => (float) $node->Price,
            'department_number'                 => (string) $node->Department,
            'price_group_number'                => $this->str($node->Price_Group) ?: null,
            'item_deposit'                      => $this->decimal($node->Item_Deposit),
            'promo_code'                        => $this->str($node->Promo_Code),
            'host_product_code'                 => $this->str($node->Host_Product_Code),
            'tax1'                              => $this->yn($node->TAX1 ?? $node->GST_On_Item),
            'tax2'                              => $this->yn($node->TAX2 ?? $node->PST_On_Item),
            'tax3'                              => $this->yn($node->TAX3),
            'tax4'                              => $this->yn($node->TAX4),
            'tax5'                              => $this->yn($node->TAX5),
            'tax6'                              => $this->yn($node->TAX6),
            'tax7'                              => $this->yn($node->TAX7),
            'tax8'                              => $this->yn($node->TAX8),
            'prompt_for_price'                  => $this->yn($node->Prompt_For_Price),
            'item_not_active'                   => $this->yn($node->Item_Not_Active),
            'tax_included_price'                => $this->yn($node->Tax_Included_Price),
            'wash_type'                         => $this->str($node->Wash_Type),
            'car_wash_controller_code'          => $this->int($node->Car_Wash_Controller_Code),
            'upsell_qty_car_wash'               => $this->int($node->Upsell_Quantity_For_Car_Wash_At_Pump),
            'petro_canada_pass_code'            => $this->int($node->Petro_Canada_PASS_Code),
            'item_desc_not_on_2nd_monitor'      => $this->yn($node->Item_Desc_Not_On_2nd_Monitor),
            'ontario_rst_tax_off'               => $this->yn($node->Ontario_RST_Tax_Off),
            'ontario_rst_tax_on'                => $this->yn($node->Ontario_RST_Tax_On),
            'federal_baked_good_item'           => $this->yn($node->Federal_Baked_Good_Item),
            'prevent_bt9000_inventory_control'  => $this->yn($node->Prevent_BT9000_Inventory_Control),
            'conexxus_product_code'             => $this->str($node->Conexxus_Product_Code),
            'car_wash_expiry_in_days'           => $this->int($node->Car_Wash_Expiry_In_Days),
            'afd_car_wash_position'             => $this->int($node->AFD_Car_Wash_Position_On_Screen),
            'age_requirements'                  => $this->int($node->Age_Requirements),
            'redemption_only'                   => $this->yn($node->Redemption_Only),
            'loyalty_card_eligible'             => $this->ynRequired((string) ($node->Loyalty_Card_Eligible ?? 'N')),
            'delivery_channel_price'            => $this->decimal($node->Delivery_Channel_Price),
            'tax_strategy_id_from_nacs'         => $this->str($node->Tax_Strategy_ID_From_NACS),
            'owner'                             => $this->str($node->Owner),
            'created_at'                        => $now,
            'updated_at'                        => $now,
        ];

        $upcs = [];
        if (isset($node->UPCs->UPC)) {
            foreach ($node->UPCs->UPC as $upc) {
                $upcVal = trim((string) $upc);
                if ($upcVal !== '') {
                    $upcs[] = [
                        'item_number' => $itemNumber,
                        'upc'         => $upcVal,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                }
            }
        }

        $qtyPricing = [];
        if (isset($node->Quantity_Pricing->Local_Quantity_Pricing)) {
            foreach ($node->Quantity_Pricing->Local_Quantity_Pricing as $lqp) {
                $qtyPricing[] = [
                    'item_number' => $itemNumber,
                    'quantity'    => (int) $lqp->Quantity,
                    'price'       => (float) $lqp->Price,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        $linked = [];
        if (isset($node->Linked_SKUs->Linked_SKU)) {
            foreach ($node->Linked_SKUs->Linked_SKU as $l) {
                $val = trim((string) $l);
                if ($val !== '') {
                    $mandatory = strtolower((string) ($l->attributes()['Mandatory'] ?? '')) === 'true';
                    $linked[] = [
                        'item_number'        => $itemNumber,
                        'linked_item_number' => $val,
                        'mandatory'          => $mandatory,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
            }
        }

        $linkable = [];
        if (isset($node->Linkable_SKUs->Linkable_SKU)) {
            foreach ($node->Linkable_SKUs->Linkable_SKU as $l) {
                $val = trim((string) $l);
                if ($val !== '') {
                    $linkable[] = [
                        'item_number'          => $itemNumber,
                        'linkable_item_number' => $val,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
            }
        }

        return compact('sku', 'upcs', 'qtyPricing', 'linked', 'linkable');
    }

    private function parseMixAndMatch(\SimpleXMLElement $node, string $now): array
    {
        $identifier = (string) $node['Mix_And_Match_Identifier'];

        $english = null;
        $french  = null;
        foreach ($node->Description ?? [] as $desc) {
            $lang = strtolower((string) ($desc['lang'] ?? ''));
            if ($lang === 'english') {
                $english = (string) $desc;
            } elseif ($lang === 'french') {
                $french = (string) $desc;
            }
        }

        $row = [
            'mix_and_match_identifier' => $identifier,
            'english_description'      => $english,
            'french_description'       => $french,
            'created_at'               => $now,
            'updated_at'               => $now,
        ];

        $members = [];
        if (isset($node->Mix_And_Match_Members_Of_Group)) {
            // Members are <Stock_Keeping_Unit> text nodes
            foreach ($node->Mix_And_Match_Members_Of_Group->Stock_Keeping_Unit ?? [] as $sku) {
                $val = trim((string) $sku);
                if ($val !== '') {
                    $members[] = [
                        'mix_and_match_identifier' => $identifier,
                        'item_number'              => $val,
                        'created_at'               => $now,
                        'updated_at'               => $now,
                    ];
                }
            }
        }

        return compact('row', 'members');
    }

    private function parseDealGroup(\SimpleXMLElement $node, string $type, string $now): void
    {
        $row = [
            'deal_group_number'       => (string) $node['Deal_Group_Number'],
            'type'                    => $type,
            'english_description'     => $this->str($node->English_Description),
            'french_description'      => $this->str($node->French_Description),
            'start_date'              => $this->parseDate($this->str($node->Start_Date)),
            'end_date'                => $this->parseDate($this->str($node->End_Date)),
            'fuel_mix_and_match_check' => $this->yn($node->Fuel_Mix_And_Match_Check),
            'dont_calculate_deal'     => $this->yn($node->Dont_Calculate_Deal),
            'deal_not_active'         => $this->yn($node->Deal_Not_Active),
            'available_in_kiosk_only' => $this->yn($node->Available_In_Kiosk_Only),
            'cpl_stacking_cpn'        => $this->yn($node->CPL_STACKING_CPN),
            'available_at_pump_only'  => $this->yn($node->Available_At_Pump_Only),
            'reason_code_for_deal'    => $this->int($node->Reason_Code_For_Deal),
            'station_id_for_deal'     => $this->str($node->Station_ID_For_Deal),
            'fixed_dollar_off'        => $this->decimal($node->Fixed_Dollar_Off),
            'max_per_customer'        => $this->int($node->Max_Per_Customer),
            'req_fuel_pos_grade'      => $this->str($node->Requires_Fuel_To_Complete_Deal->Required_Fuel_POS_Grade),
            'req_fuel_litres'         => $this->int($node->Requires_Fuel_To_Complete_Deal->Required_Fuel_Litres),
            'loyalty_card_description' => $this->str($node->Loyalty_Card_Required_To_Use_Deal_Group->Loyalty_Card_Description),
            'loyalty_card_restriction' => $this->yn($node->Loyalty_Card_Required_To_Use_Deal_Group->Card_Restriction),
            'loyalty_card_swipe_type'  => $this->int($node->Loyalty_Card_Required_To_Use_Deal_Group->Card_Swipe_Type),
            'created_at'              => $now,
            'updated_at'              => $now,
        ];

        $dealGroupId = DB::table('pb_deal_groups')->insertGetId($row);

        // CPL fuel discounts
        if (isset($node->CPL_Fuel_Discounting)) {
            $cplNode = $node->CPL_Fuel_Discounting;
            $discount = $this->decimal($cplNode->CPL_Discount_On_Fuel);
            foreach ($cplNode->POS_Grade ?? [] as $grade) {
                DB::table('pb_deal_group_cpl_fuel_discounts')->insert([
                    'deal_group_id'       => $dealGroupId,
                    'pos_grade'           => trim((string) $grade),
                    'cpl_discount_on_fuel' => $discount,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            }
        }

        // Components
        foreach ($node->Deal_Group_Components->Deal_Group_Component ?? [] as $comp) {
            $componentRow = [
                'deal_group_id'                  => $dealGroupId,
                'item_number'                    => $this->str($comp->Item) ?: null,
                'price_group_number'             => $this->str($comp->Price_Group) ?: null,
                'mix_and_match_identifier'       => $this->str($comp->Mix_And_Match) ?: null,
                'quantity'                       => (int) $comp->Quantity,
                'price_for_quantity_one'         => (float) $comp->Price_For_Quantity_One,
                'percentage_off'                 => $this->int($comp->Percentage_Off),
                'amount_off'                     => $this->decimal($comp->Amount_Off),
                'coupon_accounting_implications' => $this->str($comp->Coupon_Accounting_Implications),
                'created_at'                     => $now,
                'updated_at'                     => $now,
            ];

            $componentId = DB::table('pb_deal_group_components')->insertGetId($componentRow);

            foreach ($comp->UPCs->UPC ?? [] as $upc) {
                $val = trim((string) $upc);
                if ($val !== '') {
                    DB::table('pb_deal_group_component_upcs')->insert([
                        'deal_group_component_id' => $componentId,
                        'upc'                     => $val,
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ]);
                }
            }
        }
    }

    private function parsePayout(\SimpleXMLElement $node, string $now): array
    {
        return [
            'payout_number'       => (string) $node['Payout_Number'],
            'english_description' => (string) $node->English_Description,
            'french_description'  => $this->str($node->French_Description),
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
    }

    private function parseLoyaltyCard(\SimpleXMLElement $node, string $now): void
    {
        $cardId = DB::table('pb_loyalty_cards')->insertGetId([
            'card_name'  => trim((string) $node->Card_Name),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach ($node->Bins ?? [] as $bin) {
            DB::table('pb_loyalty_card_bins')->insert([
                'loyalty_card_id' => $cardId,
                'start_iso_bin'   => trim((string) $bin->Start_ISO_Bin),
                'end_iso_bin'     => trim((string) $bin->End_ISO_Bin),
                'min_length'      => (int) $bin->Min_Length,
                'max_length'      => (int) $bin->Max_Length,
                'check_digit'     => (int) $bin->Check_Digit,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    private function parseTenderCoupon(\SimpleXMLElement $node, string $now): array
    {
        return [
            'item_number'         => (string) $node['Item_Number'],
            'english_description' => (string) $node->English_Description,
            'french_description'  => $this->str($node->French_Description),
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
    }

    // -------------------------------------------------------------------------
    // Helper methods
    // -------------------------------------------------------------------------

    private function yn(mixed $node): ?bool
    {
        if ($node === null) {
            return null;
        }
        $val = strtoupper(trim((string) $node));
        if ($val === '') {
            return null;
        }
        return $val === 'Y';
    }

    private function ynRequired(string $value): bool
    {
        return strtoupper(trim($value)) === 'Y';
    }

    private function str(mixed $node): ?string
    {
        if ($node === null) {
            return null;
        }
        $val = trim((string) $node);
        return $val !== '' ? $val : null;
    }

    /** Like str() but preserves leading/trailing spaces (for pre-padded BT9000 descriptions). */
    private function rawStr(mixed $node): ?string
    {
        if (!isset($node[0])) {
            return null;
        }
        $val = (string) $node;
        return $val !== '' ? $val : null;
    }

    private function int(mixed $node): ?int
    {
        $val = $this->str($node);
        return $val !== null ? (int) $val : null;
    }

    private function decimal(mixed $node): ?float
    {
        $val = $this->str($node);
        return $val !== null ? (float) $val : null;
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::createFromFormat('Ymd', $value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
