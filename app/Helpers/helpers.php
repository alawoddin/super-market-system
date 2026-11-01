<?php

use App\Helpers\Money;

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        return Money::format($amount);
    }
}

if (! function_exists('qty_format')) {
    function qty_format(float|int|string|null $qty): string
    {
        return Money::qty($qty);
    }
}

if (! function_exists('amount')) {
    function amount(float|int|string|null $amount): string
    {
        return Money::number($amount);
    }
}

if (! function_exists('setting')) {
    function setting(?string $key = null, mixed $default = null): mixed
    {
        static $settings = null;

        if ($settings === null) {
            $settings = \App\Models\Setting::query()->first();
        }

        if ($key === null) {
            return $settings;
        }

        return $settings?->{$key} ?? $default;
    }
}
