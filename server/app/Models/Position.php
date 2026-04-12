<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{



    public function branches() : BelongsToMany
    {
        return $this->belongsToMany(Branch::class)->withTimestamps();
    }

    public function Employee() : HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
