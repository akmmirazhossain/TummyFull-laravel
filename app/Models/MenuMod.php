<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuMod extends Model
{
    protected $table = 'mrd_menu';

    public function food()
    {
        return $this->belongsTo(FoodMod::class, 'mrd_menu_food_id', 'mrd_food_id');
    }
}
