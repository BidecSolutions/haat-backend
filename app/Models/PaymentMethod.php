<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['name', 'code', 'active'];

    public function listings()
    {
        return $this->hasMany(Listing::class);
    }
}