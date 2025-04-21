<?php

namespace App\Services;

use App\Services\MealboxService;


use Illuminate\Support\Facades\DB;




class OrderService
{

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
}
