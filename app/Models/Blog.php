<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Blog extends Model
{
    //
    use HasFactory;

    protected $table = 'blogs';

    protected $fillable = [
        'blog_image',
        'title',
        'description',
        'created_by',
        'updated_by',
    ];

    
    public function creator(){
        return $this->belongsTo(Admin::class, 'created_by');
    }
    public function updator(){
        return $this->belongsTo(Admin::class, 'updated_by');
    }
}
