<?php

namespace App\Enums;

enum PromotionType: string
{
    case Banner = 'banner';
    case Popup = 'popup';
    case Carousel = 'carousel';
    case Deal = 'deal';

    /**
     * Return all types as array for validation.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
