<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConditionLibrary extends Model
{
    protected $table = 'condition_library';

    protected $fillable = ['label', 'slug', 'category', 'ui_color', 'svg_icon_path'];

    public function toothConditions()
    {
        return $this->hasMany(ToothCondition::class, 'condition_id');
    }
}
