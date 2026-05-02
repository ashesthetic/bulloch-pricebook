<?php

namespace Tests\Unit;

use App\Support\UpcBarcode;
use PHPUnit\Framework\TestCase;

class UpcBarcodeTest extends TestCase
{
    public function test_it_left_pads_stored_upcs_to_thirteen_digits(): void
    {
        $this->assertSame('0001234567890', UpcBarcode::normalizeStoredPayload('1234567890'));
    }

    public function test_it_can_strip_a_scanned_check_digit_before_storage(): void
    {
        $this->assertSame('0012345678901', UpcBarcode::normalizeStoredPayload('123456789012', stripCheckDigit: true));
    }

    public function test_it_calculates_a_gs1_check_digit_for_the_stored_payload(): void
    {
        $this->assertSame('00012345678905', UpcBarcode::withCheckDigit('0001234567890'));
    }

    public function test_a_generated_barcode_value_normalizes_back_to_the_same_stored_upc(): void
    {
        $storedUpc = '0001234567890';
        $scannedBarcodeValue = UpcBarcode::withCheckDigit($storedUpc);

        $this->assertSame($storedUpc, UpcBarcode::normalizeStoredPayload($scannedBarcodeValue, stripCheckDigit: true));
    }

    public function test_it_renders_an_itf_14_svg_with_the_check_digit_value(): void
    {
        $svg = UpcBarcode::itf14Svg('0001234567890');

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('height="54"', $svg);
        $this->assertStringContainsString('Barcode 00012345678905', $svg);
    }
}
