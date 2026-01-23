<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    //
    protected $table = 'appointment';
    protected $fillable = [
        'listing_id',
        'seller_id',
        'buyer_id',
        'appointment_date',
        'appointment_time',
        'latitude',
        'longitude',
        'address',
        'notes',
        'status'
    ];

    protected $casts = [
        'status' => AppointmentStatus::class,
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function buyer_id()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }
    public function seller_id()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
