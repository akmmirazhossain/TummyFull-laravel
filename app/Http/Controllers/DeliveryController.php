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

        $orderlistof = "todayafter";
        $orderstatus = "all";
        $orderperiod = "all";
        $orderarea = "all";
        // Get the token from the Authorization header
        $authorizationHeader = $request->header('Authorization');
        $TFLoginToken = str_replace('Bearer ', '', $authorizationHeader); // Remove 'Bearer ' from the token
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)->value('mrd_user_id');

        // Get the current date
        $today = Carbon::now()->format('Y-m-d');

        // Query the database based on the $orderlistof value
        $ordersQuery = DB::table('mrd_order')
            ->join('mrd_user', 'mrd_order.mrd_order_user_id', '=', 'mrd_user.mrd_user_id')
            ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->join('mrd_area', 'mrd_user.mrd_user_area', '=', 'mrd_area.mrd_area_id')
            ->join('mrd_user as delivery_user', function ($join) use ($userId) {
                $join->on('delivery_user.mrd_user_chef_id', '=', 'mrd_user.mrd_user_chef_id')
                    ->where('delivery_user.mrd_user_type', '=', 'delivery')
                    ->where('delivery_user.mrd_user_id', '=', $userId);
            })
            ->join('mrd_setting', 'mrd_setting.mrd_setting_id', '=', DB::raw('1'))
            ->select(
                'mrd_menu.mrd_menu_period',
                'mrd_menu.mrd_menu_id',
                'mrd_order.mrd_order_id',
                'mrd_order.mrd_order_date',
                'mrd_order.mrd_order_quantity',
                'mrd_order.mrd_order_total_price',
                'mrd_order.mrd_order_cash_to_get',
                'mrd_order.mrd_order_deliv_commission',
                'mrd_order.mrd_order_mealbox',
                'mrd_order.mrd_order_status',
                'mrd_user.mrd_user_id',
                'mrd_user.mrd_user_address',
                'mrd_user.mrd_user_phone',
                'mrd_user.mrd_user_first_name',
                'mrd_user.mrd_user_type',
                'mrd_user.mrd_user_chef_id',
                'mrd_user.mrd_user_has_mealbox',
                'mrd_user.mrd_user_mealbox_paid',
                'mrd_user.mrd_user_credit',
                'mrd_setting.mrd_setting_mealbox_price',
                'mrd_user.mrd_user_delivery_instruction',
                'mrd_area.mrd_area_name',
                'delivery_user.mrd_user_id as delivery_user_id'
            )
            ->orderBy('mrd_order.mrd_order_date', 'asc');




        // if ($userid !== "25") {
        //     $ordersQuery->where('md_user.mrd_area_id', '=', $orderarea);
        // }


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

        // Sort the periods explicitly, to make sure "lunch" comes before "dinner"
        foreach ($groupedOrders as $date => $periods) {
            $groupedOrders[$date] = array_replace(['lunch' => [], 'dinner' => []], $periods);
        }

        return response()->json($groupedOrders);
    }





    //MARK: deliveryUpdate
    public function deliveryUpdate(Request $request)
    {

        $orderId = $request->input('orderId');
        $userId = $request->input('userId');
        $menuId = $request->input('menuId');
        // $giveMealbox = $request->input('giveMealbox');
        $mealboxPaid = $request->input('mealboxPaid');
        $delivStatus = $request->input('delivStatus');
        $mboxPick = $request->input('mboxPick');


        // $mealPeriod = $request->input('mealType');




        $userCredit = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_credit');

        $orderTotalPrice = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->value('mrd_order_total_price');


        $menuPeriod = DB::table('mrd_menu')
            ->where('mrd_menu_id', $menuId)
            ->value('mrd_menu_period');

        $quantity = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->value('mrd_order_quantity');


        $deliveryCommission = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->value('mrd_order_deliv_commission');

        $orderDelivPrice =  $orderTotalPrice + $deliveryCommission;



        if ($delivStatus == 'delivered') {
            $notif_message =  "Your " . $menuPeriod . " has been successfully delivered.";
            $notif_credit_calc = null;


            //NOTIFICATION INSERT ON DELIVERY STATUS
            $notifInsert = DB::table('mrd_notification')->insert([
                'mrd_notif_user_id' =>
                $userId,
                'mrd_notif_message' => $notif_message,
                'mrd_notif_quantity' => $quantity,
                'mrd_notif_total_price' => $orderDelivPrice,
                'mrd_notif_type' => 'delivery'
            ]);


            //COMPARE USER CREDIT WITH PRICE
            if ($userCredit >= $orderDelivPrice) {
                $userCreditNew = $userCredit - $orderDelivPrice;
                //NOTIF INSERT
                $notifInsert = DB::table('mrd_notification')->insert([
                    'mrd_notif_user_id' =>
                    $userId,
                    'mrd_notif_message' => '৳' . $orderDelivPrice . ' has been paid from your wallet. New credit: ' . $userCredit . ' - ' . $orderDelivPrice . ' = ৳' . $userCreditNew,

                    'mrd_notif_total_price' => $notif_credit_calc,
                    'mrd_notif_type' => 'order'
                ]);

                $paymentMethod = 'wallet';
            } else {
                $userCreditNew = 0;
                $paymentMethod = 'cod';
                $cashToCollect = $orderDelivPrice - $userCredit;

                if (($userCredit != 0) && ($userCredit <= $orderDelivPrice)) {
                    $notif_message =  '৳' . $userCredit . ' has been paid from wallet & ৳' . $cashToCollect . ' via cash on delivery. New credit: ৳' . $userCreditNew;
                } else {

                    $notif_message =  '৳' . $orderDelivPrice . ' has been paid via cash on delivery.';
                }

                $notifInsert = DB::table('mrd_notification')->insert([
                    'mrd_notif_user_id' =>
                    $userId,
                    'mrd_notif_message' => $notif_message,

                    'mrd_notif_total_price' => $notif_credit_calc,
                    'mrd_notif_type' => 'order'
                ]);
            }

            //UPDATE WALLET AFTER ORDER PRICE CUT
            $userCreditUpdate = DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->update(['mrd_user_credit' => $userCreditNew]);

            //PAYMENT INSERT FOR ORDER
            $paymentInsert = DB::table('mrd_payment')->insert([
                'mrd_payment_status' =>
                'paid',
                'mrd_payment_amount' =>
                $orderDelivPrice,
                'mrd_payment_user_id' => $userId,

                'mrd_payment_order_id' => $orderId,
                'mrd_payment_for' => 'order',
                'mrd_payment_method' => $paymentMethod
            ]);


            //ORDER DELIV STATUS UPDATE
            $delivUpdate = DB::table('mrd_order')
                ->where('mrd_order_id', $orderId)
                ->update([
                    'mrd_order_status' => $delivStatus,
                    'mrd_order_user_pay_status' => 'paid'
                ]);



            //MARK: MEALBOX
            //Increment the number of mealboxes the customer has

            //GET USER INFO
            $user = DB::table('mrd_user')->where('mrd_user_id', $userId)->first();


            //INCREMENT MEALBOX COUNT IF USER MEALBOX IS ACTIVATED AND HAS LESS THAN 2
            if ($user->mrd_user_mealbox == 1 && $user->mrd_user_has_mealbox < 2) {

                DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->increment('mrd_user_has_mealbox');
            } else {
                // Optional: Handle cases where they already have 2 mealboxes or mealbox system is not activated
            }


            // Decrease the number of mealboxes based on how many are returned
            if ($mboxPick > 0 && $user->mrd_user_has_mealbox >= $mboxPick) {

                DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->decrement('mrd_user_has_mealbox', $mboxPick);

                // Increase credit by 10 or 20 depending on mboxPick
                $creditToAdd = $mboxPick * 10;

                DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->increment('mrd_user_credit', $creditToAdd);


                // Insert discount record in mrd_payment
                DB::table('mrd_payment')->insert([
                    'mrd_payment_status' => 'paid', // Considered as paid since it's a discount
                    'mrd_payment_amount' => $creditToAdd,
                    'mrd_payment_user_id' => $userId,
                    'mrd_payment_order_id' => $orderId,
                    'mrd_payment_for' => 'mealbox', // Since it's for meal orders
                    'mrd_payment_method' => 'system', // Since the system is applying the discount
                    'mrd_payment_type' => 'cashback', // Marked as discount
                    'mrd_payment_date_paid' => now(), // Timestamp of the discount application
                ]);


                $mrd_notif_message = "You’ve received ৳" . $creditToAdd . " cashback for returning your mealbox.";
                // Insert notification for the user
                DB::table('mrd_notification')->insert([
                    'mrd_notif_user_id' => $userId,
                    'mrd_notif_order_id' => $orderId,
                    'mrd_notif_message' => $mrd_notif_message,
                    'mrd_notif_type' => 'cashback',
                    'mrd_notif_date_added' => now(),
                ]);
            }




            //IF THE USER HAS PAID FOR THE MEALBOX UPDATE/INSERT 
            if ($user->mrd_user_mealbox == 1 && $user->mrd_user_mealbox_paid == 0) {

                //GET MEALBOX PRICE
                $mealboxPrice = DB::table('mrd_setting')
                    ->value('mrd_setting_mealbox_price');
                //MARK MEALBOX PAID IF IT IS UNPAID = 0
                $hasMealboxUpdate = DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->update([
                        'mrd_user_mealbox_paid' => '1'
                    ]);



                //PAYMENT INSERT FOR MEALBOX
                $paymentInsert = DB::table('mrd_payment')->insert([
                    'mrd_payment_status' =>
                    'paid',
                    'mrd_payment_amount' =>
                    $mealboxPrice,
                    'mrd_payment_user_id' => $userId,
                    'mrd_payment_for' => 'mealbox',
                    'mrd_payment_method' => 'cod'
                ]);
            }


            //MARK: order delivery increment by 1. 
            DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->increment('mrd_user_order_delivered');




            $nextOrder = DB::table('mrd_order')
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_status', 'pending')
                ->orderBy('mrd_order_date', 'asc')
                ->select('mrd_order_id', 'mrd_order_total_price')
                ->first();

            //UPDATE CASH TO GET IF USER HAS A NEXT ORDER
            if (
                $nextOrder
            ) {

                $nextOrderId = $nextOrder->mrd_order_id;
                $nextOrderDelivPrice = ($nextOrder->mrd_order_total_price) + $deliveryCommission;

                $userCreditUpdated = DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->value('mrd_user_credit');


                if (
                    $userCreditUpdated >= $nextOrderDelivPrice
                ) {
                    //$userCreditUpdatedNew = $userCreditUpdated - $nextOrderTotalPrice;
                    $cash_to_get = 0;
                } else {
                    // $userCreditUpdatedNew = 0;
                    $cash_to_get = $nextOrderDelivPrice - $userCreditUpdated;
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
            $notif_message =  "Your " . $menuPeriod . " was canceled by the delivery person.";


            //ORDER DELIVE STATUS UPDATE
            $delivUpdate = DB::table('mrd_order')
                ->where('mrd_order_id', $orderId)
                ->update(['mrd_order_status' => $delivStatus]);


            //NOTIFICATION INSERT
            $notifInsert = DB::table('mrd_notification')->insert([
                'mrd_notif_user_id' =>
                $userId,
                'mrd_notif_message' => $notif_message,

                'mrd_notif_type' => 'order'
            ]);
        } elseif ($delivStatus == 'unavailable') {
            $notif_message =  ucfirst($menuPeriod) . " delivery was unsuccessful due to customer's unavailability.";

            //UPCOMING penalty charge section here


            //ORDER DELIVE STATUS UPDATE
            $delivUpdate = DB::table('mrd_order')
                ->where('mrd_order_id', $orderId)
                ->update(['mrd_order_status' => $delivStatus]);


            //NOTIFICATION INSERT
            $notifInsert = DB::table('mrd_notification')->insert([
                'mrd_notif_user_id' =>
                $userId,
                'mrd_notif_message' => $notif_message,

                'mrd_notif_type' => 'order'
            ]);
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

            'menuId' => $menuId,
        ]);
    }
}
