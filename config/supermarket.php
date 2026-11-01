<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'currency' => 'AFN',
    'currency_symbol' => '؋',
    'currency_name' => 'Afghan Afghani',

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    */
    'money_precision' => 2,
    'quantity_precision' => 3,

    /*
    |--------------------------------------------------------------------------
    | Alert Defaults
    |--------------------------------------------------------------------------
    */
    'low_stock_days' => 0,
    'expiring_soon_days' => 7,
    'expiring_warning_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Receipt Defaults
    |--------------------------------------------------------------------------
    */
    'receipt_width' => '80mm', // 58mm or 80mm

    /*
    |--------------------------------------------------------------------------
    | Product Units
    |--------------------------------------------------------------------------
    */
    'units' => [
        'piece' => 'Piece',
        'kg' => 'Kilogram',
        'g' => 'Gram',
        'l' => 'Liter',
        'ml' => 'Milliliter',
        'box' => 'Box',
        'carton' => 'Carton',
        'pack' => 'Pack',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payments (Afghanistan — cash only)
    |--------------------------------------------------------------------------
    |
    | Card and online payments are not supported. Every sale is paid in
    | full in cash. All amounts are AFN.
    |
    */
    'payment_method' => 'cash',
    'payment_methods' => [
        'cash' => 'Cash',
    ],
    'sale_types' => [
        'cash' => 'Cash Payment',
        'credit' => 'Credit Sale',
    ],

];
