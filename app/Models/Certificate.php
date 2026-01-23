<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'certificate_name',
        'issuer',
        'issue_date',
        'expiry_date',
        'no_expiry',
        'document_path',
        'status',
    ];

    protected $casts = [
        'no_expiry' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
