<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    //MARK: deliveryList
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
            // ->join('mrd_payment', 'mrd_order.mrd_order_id', '=', 'mrd_payment.mrd_payment_order_id')
            ->select(
                'mrd_menu.mrd_menu_period',
                'mrd_menu.mrd_menu_id',
                'mrd_order.mrd_order_id',
                'mrd_order.mrd_order_date',
                'mrd_order.mrd_order_quantity',
                'mrd_order.mrd_order_total_price',
                'mrd_order.mrd_order_cash_to_get',
                'mrd_order.mrd_order_mealbox',
                'mrd_order.mrd_order_status',
                'mrd_user.mrd_user_id',
                'mrd_user.mrd_user_address',
                'mrd_user.mrd_user_phone',
                'mrd_user.mrd_user_first_name',
                'mrd_user.mrd_user_credit',
                'mrd_user.mrd_user_delivery_instruction',
                'mrd_area.mrd_area_name',
                // 'mrd_payment.mrd_payment_amount'

            )
            ->orderBy('mrd_order.mrd_order_date', 'asc');

        if ($orderarea !== "all") {
            $ordersQuery->where('mrd_area.mrd_area_id', '=', $orderarea);
        }

        if ($orderperiod !== "all") {
            $ordersQuery->where('mrd_menu.mrd_menu_period', '=', $orderperiod);
        }

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





    //MARK: deliveryUpdate
    public function deliveryUpdate(Request $request)
    {

        $delivStatus = $request->input('delivStatus');
        $orderId = $request->input('orderId');
        $userId = $request->input('userId');
        $mealPeriod = $request->input('mealType');
        $menuId = $request->input('menuId');



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






        if ($delivStatus == 'delivered') {
            $notif_message =  "Your " . $menuPeriod . " has been delivered.";
            //$notif_credit_calc = $userCredit . ' - ' . $orderTotalPrice . ' = ' . $userCreditNew;
            $notif_credit_calc = null;



            if ($userCredit >= $orderTotalPrice) {
                $userCreditNew = $userCredit - $orderTotalPrice;
                $cashToCollect = 0;
            } else {
                $userCreditNew = 0;
                $cashToCollect = $orderTotalPrice - $userCredit;
            }

            // Update the customer's wallet balance
            $userCreditUpdate = DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->update(['mrd_user_credit' => $userCreditNew]);


            //NOTIFICATION INSERT
            $notifInsert = DB::table('mrd_notification')->insert([
                'mrd_notif_user_id' =>
                $userId,
                'mrd_notif_message' => $notif_message,

                'mrd_notif_total_price' => $notif_credit_calc,
                'mrd_notif_type' => 'order'
            ]);

            //ORDER DELIVE STATUS UPDATE
            $delivUpdate = DB::table('mrd_order')
                ->where('mrd_order_id', $orderId)
                ->update(['mrd_order_status' => $delivStatus]);



            $nextOrder = DB::table('mrd_order')
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_status', 'pending')
                ->orderBy('mrd_order_date', 'asc')
                ->select('mrd_order_id', 'mrd_order_total_price')
                ->first();


            if (
                $nextOrder
            ) {

                $nextOrderId = $nextOrder->mrd_order_id;
                $nextOrderTotalPrice = $nextOrder->mrd_order_total_price;



                $userCreditUpdated = DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->value('mrd_user_credit');


                // 200 INITITAL CREDIT 

                // 2024-08-01  CALCULATED
                // 150 
                // coc = 0 

                // 2024-08-02
                // 50 - 100 = -50  (subtotal credit)
                // coc = 50

                // 2024-08-05
                // -50 - 150 = -200  (subtotal credit)
                // coc = 0



                $userCreditUpdated = DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->value('mrd_user_credit');

                if (
                    $userCreditUpdated >= $nextOrderTotalPrice
                ) {
                    //$userCreditUpdatedNew = $userCreditUpdated - $nextOrderTotalPrice;
                    $cash_to_get = 0;
                } else {
                    // $userCreditUpdatedNew = 0;
                    $cash_to_get = $nextOrderTotalPrice - $userCreditUpdated;
                }

                //CASH TO GET UPDATE
                $cashToGet = DB::table('mrd_order')
                    ->where('mrd_order_id', $nextOrderId)
                    ->update(['mrd_order_cash_to_get' => $cash_to_get]);
            }
        }
        //elseif (
        //     $delivStatus == 'delivered_with_due'
        // ) {

        //     $notif_message =  "Your " . $menuPeriod . " has been delivered.";
        //     $userCreditNew = $userCredit - $orderTotalPrice;
        //     $notif_credit_calc = $userCredit . ' - ' . $orderTotalPrice . ' = ' . $userCreditNew;

        //     //CREDIT UPDATE USER TABLE
        //     $userCreditUpdate = DB::table('mrd_user')
        //         ->where('mrd_user_id', $userId)
        //         ->update(['mrd_user_credit' => $userCreditNew]);


        //     //FIND THE NEXT ORDER ID AND ITS TOTAL PRICE FOR THE SAME USERID
        //     $nextOrder = DB::table('mrd_order')
        //         ->where('mrd_order_user_id', $userId)
        //         ->where('mrd_order_id', '>', $orderId)
        //         ->orderBy('mrd_order_id', 'asc')
        //         ->select('mrd_order_id', 'mrd_order_total_price')
        //         ->first();


        //     if (
        //         $nextOrder
        //     ) {

        //         $nextOrderId = $nextOrder->mrd_order_id;
        //         $nextOrderTotalPrice = $nextOrder->mrd_order_total_price;


        //         //GET USER CREDIT
        //         $userCredit = DB::table('mrd_user')
        //             ->where('mrd_user_id', $userId)
        //             ->value('mrd_user_credit');

        //         $subtotal = $userCredit - $nextOrderTotalPrice;

        //         if ($subtotal > 0) {
        //             $cash_to_get = 0;
        //         } else {

        //             $cash_to_get = abs($subtotal);
        //         }


        //         //CASH TO GET UPDATE
        //         $cashToGet = DB::table('mrd_order')
        //             ->where('mrd_order_id', $nextOrderId)
        //             ->update(['mrd_order_cash_to_get' => $cash_to_get]);
        //     }
        // } 
        elseif ($delivStatus == 'cancelled') {
            $notif_message =  "Your " . $menuPeriod . " was canceled.";
            $notif_credit_calc =
                null;
        } elseif ($delivStatus == 'unavailable') {
            $notif_message =  "Unable to deliver your " . $menuPeriod . " due to unavailability.";
            $notif_credit_calc =
                null;
        }






        return response()->json([
            'success' => true,
            'userCredit' => $userCredit,
            // 'nextOrderTotalPrice' => $nextOrderTotalPrice,
            // 'subtotal' => $subtotal,
            'notif' => $notif_message,
            'delivStatus' => $delivStatus,
            'orderId' => $orderId,
            'userId' => $userId,
            'mealPeriod' => $mealPeriod,
            'menuId' => $menuId,
        ]);
    }
}
