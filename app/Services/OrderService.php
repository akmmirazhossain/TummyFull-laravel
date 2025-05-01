<?php

namespace App\Services;

use App\Services\MealboxService;


use Illuminate\Support\Facades\DB;




class OrderService
{



    public static function orderUpdate(
        $userId,
        $menuId,
        $orderDate,
        $orderFor = null,
        $orderType = null,
        $orderQty = null,
        $orderStatus = null,
        $orderPayStatus = null,
        $orderRating = null,
        $orderFeedback = null,
        $MealboxService = null,
        $CreditService = null
    ) {

        $updateData = [];
        // $updateData = [
        //     'mrd_order_user_id'        => $userId,
        //     'mrd_order_menu_id'        => $menuId,
        //     'mrd_order_date'           => $orderDate,

        // ];

        if (!is_null($orderStatus)) {
            $updateData['mrd_order_status'] = $orderStatus;
        }

        if (!is_null($orderFor)) {
            $updateData['mrd_order_for'] = $orderFor;
        }

        if (!is_null($orderType)) {
            $updateData['mrd_order_type'] = $orderType;
        }

        if (!is_null($orderQty)) {
            $updateData['mrd_order_quantity'] = $orderQty;
        }

        if (!is_null($orderQty) && !is_null($userId)) {

            $updateData['mrd_order_mealbox']       = $MealboxService->mealboxGive($userId, $orderQty);
            $updateData['mrd_order_mealbox_extra'] = $MealboxService->mealboxExtra($userId, $orderQty);
            $updateData['mrd_order_total_price']   = $CreditService->totalPrice($userId, $orderQty);
            $updateData['mrd_order_cash_to_get']   = $CreditService->cashToGet($userId, $orderQty);
        }

        if (!is_null($orderPayStatus)) {
            $updateData['mrd_order_user_pay_status'] = $orderPayStatus;
        }

        if (!is_null($orderRating)) {
            $updateData['mrd_order_rating'] = $orderRating;
        }

        if (!is_null($orderFeedback)) {
            $updateData['mrd_order_feedback'] = $orderFeedback;
        }

        return DB::table('mrd_order')
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_date', $orderDate)
            ->update($updateData);
    }



    public static function orderInsert(
        $userId,
        $menuId,
        $orderDate,
        $orderFor = null,
        $orderType = null,
        $orderQty = null,
        $orderStatus = null,
        $orderPayStatus = null,
        $orderRating = null,
        $orderFeedback = null,
        $MealboxService = null,
        $CreditService = null
    ) {
        $insertData = [
            'mrd_order_user_id'        => $userId,
            'mrd_order_menu_id'        => $menuId,
            'mrd_order_date'           => $orderDate,

        ];

        if (!is_null($orderStatus)) {
            $insertData['mrd_order_status'] = $orderStatus;
        }

        if (!is_null($orderFor)) {
            $insertData['mrd_order_for'] = $orderFor;
        }

        if (!is_null($orderType)) {
            $insertData['mrd_order_type'] = $orderType;
        }

        if (!is_null($orderQty)) {
            $insertData['mrd_order_quantity'] = $orderQty;
        }

        if (!is_null($orderQty) && !is_null($userId)) {

            $insertData['mrd_order_mealbox']       = $MealboxService->mealboxGive($userId, $orderQty);
            $insertData['mrd_order_mealbox_extra'] = $MealboxService->mealboxExtra($userId, $orderQty);
            $insertData['mrd_order_total_price']   = $CreditService->totalPrice($userId, $orderQty);
            $insertData['mrd_order_cash_to_get']   = $CreditService->cashToGet($userId, $orderQty);
        }

        if (!is_null($orderPayStatus)) {
            $insertData['mrd_order_user_pay_status'] = $orderPayStatus;
        }

        if (!is_null($orderRating)) {
            $insertData['mrd_order_rating'] = $orderRating;
        }

        if (!is_null($orderFeedback)) {
            $insertData['mrd_order_feedback'] = $orderFeedback;
        }

        return DB::table('mrd_order')->insertGetId($insertData);
    }





    //MARK: ORDER STATUS
    public  function getOrderStatus($userId, $menuId, $date, $status)
    {
        // Check if the order exists using DB Facade
        $orderExistance = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)
            ->where('mrd_order_status', $status)
            ->exists();

        return $orderExistance ? 'enabled' : 'disabled';
    }

    public function getQuantity($userId, $menuId, $date)
    {
        $order = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)
            ->first();

        // Return the quantity if order exists, otherwise return 0
        return $order ? $order->mrd_order_quantity : 0;
    }

    public function orderType($userId, $menuId, $date, $status)
    {
        $orderType = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)
            ->where('mrd_order_status', $status)
            ->value('mrd_order_type');

        return $orderType === 'custom' ? 'custom' : 'default';
    }

    public function getOrderId($userId, $menuId, $date)
    {
        $orderId = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)

            ->value('mrd_order_id');

        return $orderId;
    }


    public static function getNextOrder(
        $userId
    ) {


        $nextOrder = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_status', 'pending')
            ->orderBy('mrd_order_date', 'asc')
            ->select('mrd_order_id', 'mrd_order_user_id', 'mrd_order_quantity', 'mrd_order_total_price', 'mrd_order_deliv_commission')
            ->first();
    }
}
