<?php

namespace App\Helpers;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        return config('supermarket.currency_symbol', '؋').' '.self::number($amount);
    }

    /** 1210.00 -> "1,210", 12.50 -> "12.5" (no extra zeros). */
    public static function number(float|int|string|null $amount): string
    {
        $value = number_format((float) $amount, config('supermarket.money_precision', 2), '.', ',');

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }

    public static function currency(): string
    {
        return config('supermarket.currency', 'AFN');
    }

    public static function round(float|int|string|null $amount): string
    {
        return number_format((float) $amount, config('supermarket.money_precision', 2), '.', '');
    }

    public static function qty(float|int|string|null $qty): string
    {
        $precision = config('supermarket.quantity_precision', 3);
        $formatted = number_format((float) $qty, $precision, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}
