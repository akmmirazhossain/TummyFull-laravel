<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MealDisableController extends Controller
{
    public function getDisabledMeals()
    {
        $disabledMeals = DB::table('mrd_meal_disable')
            ->select(
                'mrd_meal_disable_day as date',
                'mrd_meal_disable_lunch as lunch',
                'mrd_meal_disable_dinner as dinner',
                'mrd_meal_disable_message as message',

            )
            ->get()
            ->groupBy('date');

        return response()->json(['disabledMeals' => $disabledMeals]);
    }
}
