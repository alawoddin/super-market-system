<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';

    public function label(): string
    {
        return 'Cash';
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
