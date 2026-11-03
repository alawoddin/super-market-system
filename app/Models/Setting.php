<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'shop_name', 'logo', 'receipt_qr', 'phone', 'whatsapp', 'address', 'currency',
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
            'shop_name' => 'غزنویان هایپر',
            'currency' => 'AFN',
            'receipt_footer' => 'Thank you for shopping with us!',
            'low_stock_alert' => 5,
            'expiration_alert_days' => 7,
            'receipt_width' => '80mm',
            'whatsapp' => '0747333670',
        ]);
    }
}