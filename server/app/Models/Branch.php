<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Branch extends Model
{

        protected $fillable = [
            'branch_name',
            'region',
            'phone',
            'clinic_owner_id'
        ];

        public $timestamps = false;

    public function clinicOwner(): HasOne
    {
        return $this->hasOne(ClinicOwner::class);
    }

    public function Account() : HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function AccountTransaction(): HasOne
    {
        return $this->hasOne(AccountTransaction::class);
    }

    public function accountTransactions() : BelongsToMany
    {
        return $this->belongsToMany(AccountTransaction::class)->withTimestamps();
    }

    public function positions(): BelongsToMany
    {
        return $this->belongsToMany(Position::class)->withTimestamps();
    }

    public function clinicMaterials(): BelongsToMany
    {
        return $this->belongsToMany(ClinicMaterial::class)->withTimestamps();
    }

    public function ClinicAsset() : HasOne
    {
        return $this->hasOne(ClinicAsset::class);
    }

    public function ClinicExpense() : HasMany
    {
        return $this->hasMany(ClinicExpense::class);
    }

    public function DentalXray() : HasMany
    {
        return $this->hasMany(DentalXray::class);
    }

    public function Employee() : HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function Patient() : HasMany
    {
        return $this->hasMany(Patient::class);
    }

    public function Prescription() : HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function Allergy() : HasMany
    {
        return $this->hasMany(Allergy::class);
    }

    public function appointments() {
        return $this->hasMany(Appointment::class);
    }

    public function treatments() : HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function inventoryStocks() : HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function orders() : HasMany
    {
        return $this->hasMany(Order::class);
    }
}
