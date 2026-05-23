<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicOwner extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'total_amount_due',
        'total_amount_paid'
    ];

    public $timestamps = true;

    public function branches(): hasMany
    {
        return $this->hasMany(Branch::class);
    }
}
