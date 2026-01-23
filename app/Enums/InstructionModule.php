<?php

namespace App\Enums;

enum InstructionModule: string
{
    case Motors = 'motors';
    case Property = 'property';
    case Marketplace = 'marketplace';
    case General = 'general';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
