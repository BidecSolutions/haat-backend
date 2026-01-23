<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    //
    use HasFactory;
    protected $table = 'area';
    protected $fillable = ['city_id', 'name', 'name_ar'];

    public function city(){
        return $this->belongsTo(City::class, 'city_id');
    }
}
