<?php

namespace App\Enums;

enum Position :string {
    case Receptionist = 'receptionist';
    case Admin = 'admin';
    case Doctor = 'doctor';
    case DoctorAssistant = 'doctor assistant';
}
