<?php
namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Completed = 'completed';
}
