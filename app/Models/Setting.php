<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'shop_name', 'logo', 'phone', 'address', 'currency',
        'receipt_header', 'receipt_footer', 'low_stock_alert',
        'expiration_alert_days', 'receipt_width',
    ];

    protected function casts(): array
    {
        return [
            'low_stock_alert' => 'integer',
            'expiration_alert_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'shop_name' => 'My Supermarket',
            'currency' => 'AFN',
            'receipt_footer' => 'Thank you for shopping with us!',
            'low_stock_alert' => 5,
            'expiration_alert_days' => 7,
            'receipt_width' => '80mm',
        ]);
    }
}