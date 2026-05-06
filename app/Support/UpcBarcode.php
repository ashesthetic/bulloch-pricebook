<?php

namespace App\Support;

class UpcBarcode
{
    public static function normalizeStoredPayload(?string $value, bool $stripCheckDigit = false): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        if ($stripCheckDigit && strlen($digits) > 1) {
            $digits = substr($digits, 0, -1);
        }

        return str_pad(substr($digits, -13), 13, '0', STR_PAD_LEFT);
    }

    public static function checkDigit(string $payload): int
    {
        $digits = preg_replace('/\D+/', '', $payload);
        $sum = 0;
        $weight = 3;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $weight;
            $weight = $weight === 3 ? 1 : 3;
        }

        return (10 - ($sum % 10)) % 10;
    }

    public static function withCheckDigit(?string $payload): ?string
    {
        $normalized = self::normalizeStoredPayload($payload);

        if ($normalized === null) {
            return null;
        }

        return $normalized . self::checkDigit($normalized);
    }

    /**
     * Convert a stored 13-digit UPC payload to a 13-digit EAN-13 number (with check digit).
     * The stored format is a 13-digit ITF-14 payload (no check digit). Dropping the leading
     * digit gives the 12-digit EAN-13 payload, to which we append the GS1 check digit.
     */
    public static function ean13(?string $storedUpc): ?string
    {
        $normalized = self::normalizeStoredPayload($storedUpc);

        if ($normalized === null) {
            return null;
        }

        $payload = substr($normalized, 1); // 12-digit EAN-13 payload

        return $payload . self::checkDigit($payload);
    }

    public static function ean13Svg(?string $storedUpc, int $height = 54): string
    {
        $ean13 = self::ean13($storedUpc);

        if ($ean13 === null) {
            return '';
        }

        // EAN-13 encoding tables (0=space/white, 1=bar/black)
        $L = ['0001101','0011001','0010011','0111101','0100011','0110001','0101111','0111011','0110111','0001011'];
        $G = ['0100111','0110011','0011011','0100001','0011101','0111001','0000101','0010001','0001001','0010111'];
        $R = ['1110010','1100110','1101100','1000010','1011100','1001110','1010000','1000100','1001000','1110100'];

        // Left-group parity pattern per first digit (0=L table, 1=G table)
        $parity = [
            '0' => [0,0,0,0,0,0], '1' => [0,0,1,0,1,1], '2' => [0,0,1,1,0,1],
            '3' => [0,0,1,1,1,0], '4' => [0,1,0,0,1,1], '5' => [0,1,1,0,0,1],
            '6' => [0,1,1,1,0,0], '7' => [0,1,0,1,0,1], '8' => [0,1,0,1,1,0],
            '9' => [0,1,1,0,1,0],
        ];

        $leftParity = $parity[$ean13[0]];
        $bits = str_repeat('0', 11) . '101'; // quiet zone + start guard

        for ($i = 1; $i <= 6; $i++) {
            $d = (int) $ean13[$i];
            $bits .= $leftParity[$i - 1] === 0 ? $L[$d] : $G[$d];
        }

        $bits .= '01010'; // center guard

        for ($i = 7; $i <= 12; $i++) {
            $bits .= $R[(int) $ean13[$i]];
        }

        $bits .= '101' . str_repeat('0', 7); // end guard + quiet zone

        $module = 2;
        $svgWidth = strlen($bits) * $module; // 113 modules × 2 = 226px
        $rects = [];

        $i = 0;
        $len = strlen($bits);
        while ($i < $len) {
            if ($bits[$i] === '1') {
                $start = $i;
                while ($i < $len && $bits[$i] === '1') {
                    $i++;
                }
                $rects[] = sprintf('<rect x="%d" y="0" width="%d" height="%d" />', $start * $module, ($i - $start) * $module, $height);
            } else {
                $i++;
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="Barcode %s" preserveAspectRatio="none" style="display:block;width:100%%;height:%dpx"><rect width="100%%" height="100%%" fill="white" />%s</svg>',
            $svgWidth, $height, $svgWidth, $height,
            e($ean13), $height,
            implode('', $rects),
        );
    }

    public static function itf14Svg(?string $payload, int $height = 54): string
    {
        $value = self::withCheckDigit($payload);

        if ($value === null) {
            return '';
        }

        $patterns = [
            '0' => 'nnwwn',
            '1' => 'wnnnw',
            '2' => 'nwnnw',
            '3' => 'wwnnn',
            '4' => 'nnwnw',
            '5' => 'wnwnn',
            '6' => 'nwwnn',
            '7' => 'nnnww',
            '8' => 'wnnwn',
            '9' => 'nwnwn',
        ];

        $narrow = 2;
        $wide = 5;
        $quietZone = 12;
        $bars = [];
        $x = $quietZone;

        $addBar = function (int $width) use (&$bars, &$x, $height): void {
            $bars[] = [$x, $width];
            $x += $width;
        };

        $addSpace = function (int $width) use (&$x): void {
            $x += $width;
        };

        $widthFor = fn (string $unit): int => $unit === 'w' ? $wide : $narrow;

        $addBar($narrow);
        $addSpace($narrow);
        $addBar($narrow);
        $addSpace($narrow);

        for ($i = 0; $i < strlen($value); $i += 2) {
            $barPattern = $patterns[$value[$i]];
            $spacePattern = $patterns[$value[$i + 1]];

            for ($j = 0; $j < 5; $j++) {
                $addBar($widthFor($barPattern[$j]));
                $addSpace($widthFor($spacePattern[$j]));
            }
        }

        $addBar($wide);
        $addSpace($narrow);
        $addBar($narrow);

        $width = $x + $quietZone;
        $rects = array_map(
            fn (array $bar): string => sprintf('<rect x="%d" y="0" width="%d" height="%d" />', $bar[0], $bar[1], $height),
            $bars,
        );

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d" role="img" aria-label="Barcode %s" preserveAspectRatio="none" style="display:block;width:100%%;height:%dpx"><rect width="100%%" height="100%%" fill="white" />%s</svg>',
            $width,
            $height,
            $width,
            $height,
            e($value),
            $height,
            implode('', $rects),
        );
    }
}
