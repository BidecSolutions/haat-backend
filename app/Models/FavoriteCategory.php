<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteCategory extends Model
{
    protected $fillable = ['user_id', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

