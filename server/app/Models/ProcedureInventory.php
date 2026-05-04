<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureInventory extends Model
{
    protected $table = 'procedure_inventory';
    protected $fillable = ['procedure_id', 'inventory_item_id', 'unit_count', 'is_optional'];

    public function procedure()
    {
        return $this->belongsTo(Procedure::class);
    }
    public function stock()
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }
}
