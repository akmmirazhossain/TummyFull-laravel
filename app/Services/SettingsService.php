<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;

class SettingsService
{
    public static function perMealPrice()
    {
        $perMealPrice = DB::table('mrd_setting')
            ->select('mrd_setting_meal_price')
            ->first();

        return $perMealPrice->mrd_setting_meal_price;
    }
}
