<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    //MARK: Delivery Today
    public function deliveryList(Request $request)
    {

        // Set the variables for date criteria and order status
        $orderlistof = "todayafter"; // Default to 'alltime' if not provided
        $orderstatus = "all";
        $orderperiod = "all";
        $orderarea = "all";

        // Get the current date
        $today = Carbon::now()->format('Y-m-d');

        // Query the database based on the $orderlistof value
        $ordersQuery = DB::table('mrd_order')
            ->join('mrd_user', 'mrd_order.mrd_order_user_id', '=', 'mrd_user.mrd_user_id')
            ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->join('mrd_area', 'mrd_user.mrd_user_area', '=', 'mrd_area.mrd_area_id')
            ->select(
                'mrd_menu.mrd_menu_period',
                'mrd_menu.mrd_menu_id',
                'mrd_order.mrd_order_date',
                'mrd_order.mrd_order_mealbox',
                'mrd_user.mrd_user_address',
                'mrd_user.mrd_user_phone',
                'mrd_user.mrd_user_first_name',
                'mrd_user.mrd_user_delivery_instruction',
                'mrd_order.mrd_order_status',
                'mrd_area.mrd_area_name'

            )
            ->orderBy('mrd_order.mrd_order_date', 'asc');


        if ($orderarea !== "all") {
            $ordersQuery->where('mrd_area.mrd_area_id', '=', $orderarea);
        }

        if ($orderperiod !== "all") {
            $ordersQuery->where('mrd_menu.mrd_menu_period', '=', $orderperiod);
        }

        // Modify query based on the value of $orderlistof
        if ($orderlistof === 'today') {
            $ordersQuery->whereDate('mrd_order.mrd_order_date', '=', $today);
        } elseif ($orderlistof === 'todayafter') {
            $ordersQuery->whereDate('mrd_order.mrd_order_date', '>=', $today);
        }

        // Modify query based on the value of $orderstatus
        if ($orderstatus !== "all") {
            $ordersQuery->where('mrd_order.mrd_order_status', '=', $orderstatus);
        }

        // Fetch the orders
        $orders = $ordersQuery->get();

        // Initialize an empty associative array to store grouped orders
        $groupedOrders = [];

        foreach ($orders as $order) {
            $date = $order->mrd_order_date;
            $period = $order->mrd_menu_period;


            // Check if the date key exists in $groupedOrders, if not, initialize it as an empty array
            if (!isset($groupedOrders[$date])) {
                $groupedOrders[$date] = [];
            }


            if (!isset($groupedOrders[$date])) {
                $groupedOrders[$date] = [];
            }

            // Check if the period key exists in $groupedOrders[$date], if not, initialize it as an empty array
            if (!isset($groupedOrders[$date][$period])) {
                $groupedOrders[$date][$period] = [];
            }

            // Push the current order into the array under its corresponding date and period
            $groupedOrders[$date][$period][] = $order;
        }



        return response()->json($groupedOrders);
    }

    //MARK: DeliveryLater
    public function orderListChefLater()
    {
    }
}
