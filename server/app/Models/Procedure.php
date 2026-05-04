<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Procedure extends Model
{
    protected $fillable = ['name', 'slug', 'category', 'base_price', 'dentist_commission', 'assistant_commission', 'is_active'];

    public function inventoryRequirements(): HasMany
    {
        return $this->hasMany(ProcedureInventory::class);
    }

    public function stocks(): BelongsToMany
    {
        return $this->belongsToMany(InventoryStock::class, 'procedure_inventory')
                    ->withPivot('unit_count', 'is_optional');
    }
}
