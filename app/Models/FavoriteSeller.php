<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteSeller extends Model
{
    protected $fillable = ['user_id', 'seller_id'];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
