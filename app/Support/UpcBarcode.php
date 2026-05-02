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
