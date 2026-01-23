<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Governorates extends Model
{
    //
    use HasFactory;
    protected $table = 'governorates';
    protected $fillable = ['region_id', 'name', 'name_ar'];

    public function region()
    {
        return $this->belongsTo(Regions::class, 'region_id');
    }
    public function cities()
    {
        return $this->hasMany(City::class, 'governorate_id');
    }
}
