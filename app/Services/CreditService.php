<?php

namespace App\Services;

use App\Services\MealboxService;


use Illuminate\Support\Facades\DB;




class CreditService
{

    //MARK: CASH TO GET
    public function cashToGet($userId, $quantity)
    {
        $totalPrice = $this->totalPrice($userId, $quantity);
        $userCredit = DB::table('mrd_user')->where('mrd_user_id', $userId)->value('mrd_user_credit');

        $subtotal = $userCredit - $totalPrice;

        return $subtotal > 0 ? 0 : abs($subtotal);
    }

    //MARK: TOTAL PRICE
    public function totalPrice($userId, $quantity)
    {
        $mealboxService = new MealboxService();

        $mealPrice = DB::table('mrd_setting')->value('mrd_setting_meal_price');
        $mealboxPrice = DB::table('mrd_setting')->value('mrd_setting_mealbox_price');
        $delivCharge = DB::table('mrd_setting')->value('mrd_setting_commission_delivery');


        //GET MEALBOX INFO
        $user = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->select('mrd_user_mealbox', 'mrd_user_has_mealbox')
            ->first();

        $userMealboxActive = $user->mrd_user_mealbox;


        if ($userMealboxActive) {

            $extraBox = $mealboxService->mealboxExtra($userId, $quantity);
            $extraBoxPrice = $extraBox * $mealboxPrice;
        } else {
            $extraBoxPrice = 0;
        }

        // (MEAL PRICE x QTY) + MEALBOX PRICE + DELIV

        $totalPrice = ($mealPrice *  $quantity) + $extraBoxPrice +  $delivCharge;
        return $totalPrice;
    }

    public static function userCredit($userId)
    {
        $userCredit = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_credit');

        return $userCredit;
    }
}
