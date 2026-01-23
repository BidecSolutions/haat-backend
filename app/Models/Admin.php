<?php

// app/Models/Admin.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $guard_name = 'admin-api';
    protected $hidden = ['password'];
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'status', 'created_by',
    ];

    public function blogs(){
        return $this->hasMany(Blog::class, 'created_by');
    }

}
