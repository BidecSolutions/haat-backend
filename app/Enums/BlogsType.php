<?php

namespace App\Enums;

enum BlogsType: string
{
    case HOME = 'home';
    case MARKETPLACE = 'marketplace';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
