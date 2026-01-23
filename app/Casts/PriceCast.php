<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class PriceCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        // remove .00 and format
        return number_format((int) $value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        // store raw numeric value in DB
        return $value;
    }
}
