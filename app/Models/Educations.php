<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Educations extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'education_provider',
        'qualification',
        'start_date',
        'end_date',
        'currently_studying',
        'status',
    ];

    protected $casts = [
        'currently_studying' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
