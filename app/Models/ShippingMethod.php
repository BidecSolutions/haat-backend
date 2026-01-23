<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = ['name', 'code', 'description', 'active'];

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }
}