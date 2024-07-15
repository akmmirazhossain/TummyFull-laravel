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
                'mrd_order.mrd_order_id',
                'mrd_order.mrd_order_date',
                'mrd_order.mrd_order_quantity',
                'mrd_order.mrd_order_total_price',
                'mrd_order.mrd_order_mealbox',
                'mrd_order.mrd_order_status',
                'mrd_user.mrd_user_id',
                'mrd_user.mrd_user_address',
                'mrd_user.mrd_user_phone',
                'mrd_user.mrd_user_first_name',
                'mrd_user.mrd_user_delivery_instruction',
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
    public function deliveryUpdate(Request $request)
    {

        $delivStatus = $request->input('delivStatus');
        $orderId = $request->input('orderId');
        $userId = $request->input('userId');
        $mealPeriod = $request->input('mealType');
        $menuId = $request->input('menuId');


        // Fetch user ID based on session token



        $userCredit = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_credit');

        $orderTotalPrice = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->value('mrd_order_total_price');


        $orderQuantity = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->value('mrd_order_quantity');

        $menuPeriod = DB::table('mrd_menu')
            ->where('mrd_menu_id', $menuId)
            ->value('mrd_menu_period');



        // $formattedDate = Carbon::parse($date)->format('F j (l)');



        if ($delivStatus == 'delivered') {
            $notif_message =  "Your " . $menuPeriod . " has been delivered. Quantity (" . $orderQuantity . ")";
            $userCreditNew = $userCredit - $orderTotalPrice;
            $notif_credit_calc = $userCredit . ' - ' . $orderTotalPrice . ' = ' . $userCreditNew;

            $userCreditUpdate = DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->update(['mrd_user_credit' => $userCreditNew]);
        } elseif ($delivStatus == 'cancelled') {
            $notif_message =  "Your " . $menuPeriod . " was canceled.";
            $notif_credit_calc =
                null;
        } elseif ($delivStatus == 'unavailable') {
            $notif_message =  "Unable to deliver your " . $menuPeriod . " due to unavailability.";
            $notif_credit_calc =
                null;
        }




        $notifInsert = DB::table('mrd_notification')->insert([
            'mrd_notif_user_id' =>
            $userId,
            'mrd_notif_message' => $notif_message,
            'mrd_notif_credit_calc' => $notif_credit_calc,
            'mrd_notif_type' => 'order'
        ]);


        $delivUpdate = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->update(['mrd_order_status' => $delivStatus]);


        return response()->json([
            'success' => true,
            'delivStatus' => $delivStatus,
            'orderId' => $orderId,
            'userId' => $userId,
            'mealPeriod' => $mealPeriod,
            'menuId' => $menuId,
        ]);
    }
}
