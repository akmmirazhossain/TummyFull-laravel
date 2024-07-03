<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FoodMod extends Model
{
    protected $table = 'mrd_food';
    protected $primaryKey = 'mrd_food_id';
    public $timestamps = false;
}
