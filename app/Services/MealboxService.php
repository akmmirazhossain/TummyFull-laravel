<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;


class MealboxService
{
    public function mealboxExtra($userId, $quantity)
    {
        $user = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->select('mrd_user_mealbox', 'mrd_user_has_mealbox')
            ->first();

        $userMealboxActive = $user->mrd_user_mealbox;
        $userHasMealbox = $user->mrd_user_has_mealbox;

        return $userMealboxActive ? max($quantity - $userHasMealbox, 0) : 0;
    }

    public function mealboxStat($token)
    {
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $token)
            ->value('mrd_user_id');

        return DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_mealbox');
    }


    public function getMealboxStatus($userId, $menuId, $date)
    {
        // Query to retrieve mrd_order_status using DB facade
        $mealboxStatus = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)
            ->value('mrd_order_mealbox');

        return $mealboxStatus;
    }


    // Stub for extraMealbox logic
    public function extraMealbox($userId, $quantity)
    {
        $userId = '';
        $orderId = '';
        $quantity = '';
        $mealPrice = ''; //Per meal price
        $deliveryComm = '';


        $mealboxIsActive = '';
        $mealboxPrice = '';
        $mealboxHasNow = '';
        $mealboxHasAfter = '';
        $mealboxExtra = '';
        $mealboxGive = '';
        $mealboxPick = '';
        $mealboxReturn = '';



        $totalPrice = '';
    }
}
