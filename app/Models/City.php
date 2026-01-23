<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $table = 'cities';
    protected $fillable = ['governorate_id','region_id', 'name', 'name_ar'];

    public function governorate()
    {
        return $this->belongsTo(Governorates::class, 'governorate_id');
    }
    public function region()
    {
        return $this->belongsTo(Regions::class, 'region_id');
    }
}