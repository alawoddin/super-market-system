<?php

namespace App\Helpers;

class Money
{
    public static function format(float|int|string|null $amount): string
    {
        $value = number_format((float) $amount, config('supermarket.money_precision', 2), '.', ',');

        return config('supermarket.currency_symbol', '؋').' '.$value;
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
