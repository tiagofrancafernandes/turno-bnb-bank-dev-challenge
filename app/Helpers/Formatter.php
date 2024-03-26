<?php

namespace App\Helpers;

class Formatter
{
    public static function floatFormat(
        null|int|float|string $value,
        int $decimals = 2,
    ): float {
        $value = is_numeric($value) ? floatval($value) : '0.00';

        return floatval(number_format($value, $decimals, '.', ''));
    }
}
