<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Services\CreditService;
use App\Services\MealboxService;
use App\Services\NotifService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use App\Services\OrderService;

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
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

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
        $mealboxPicked = $request->input('mboxPick');
        $date = $request->input('date');


        // $mealPeriod = $request->input('mealType');

        $MealboxService = new MealboxService();
        $CreditService = new CreditService();


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


        $perMealPrice = DB::table('mrd_setting')
            ->select('mrd_setting_meal_price')
            ->first();

        $deliveryCommission = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->value('mrd_order_deliv_commission');

        $orderDate = DB::table('mrd_order')
            ->where('mrd_order_id', $orderId)
            ->value('mrd_order_date');

        $orderDelivPrice =  $orderTotalPrice;

        $formattedDate = Carbon::parse($orderDate)->format('M j (D)');

        //MARK: DELIVERED
        if ($delivStatus == 'delivered') {
            $notifMessage =   'Delivered ' . $menuPeriod . ', ' . $formattedDate;


            $mealboxExtra = $MealboxService->mealboxExtra($userId, $quantity);

            NotifService::notifInsert($userId, $orderId, $notifMessage, 'order', 'delivery', $quantity, $mealboxExtra, $orderDelivPrice,  '0');


            //MARK: MEALBOX
            $user = DB::table('mrd_user')->where('mrd_user_id', $userId)->first();

            //PAY EXTRA MEALBOX PRICE
            if ($user->mrd_user_mealbox) {
                $mealboxExtra = $MealboxService->mealboxExtra($userId,  $quantity);
                $mealboxExtraPrice = $MealboxService->mealboxExtraPrice($mealboxExtra);

                //PAYMENT INSERT FOR MEALBOX IF EXTRA MEALBOX PRICE IS NOT 0
                if ($mealboxExtraPrice) {
                    $paymentId = PaymentService::paymentInsert(
                        $userId,
                        $orderId,
                        $mealboxExtraPrice,
                        'mealbox',
                        'paid',
                        'payment',
                        null,
                        null,
                        null,
                        null,
                        null,
                        null
                    );
                }
            } else {
                $mealboxExtraPrice = 0;
            }

            //COMPARE USER CREDIT WITH PRICE
            if ($userCredit >= $orderDelivPrice) {

                //PURPOSE: If credit is MORE than TotalPrice, then pay entirely from wallet. 
                $userCreditNew = $userCredit - $orderDelivPrice;
                // $mealboxExtra = $MealboxService->mealboxExtra($userId, $quantity);
                $notifMessage = 'Paid ৳' . $orderDelivPrice . ' from wallet. New credit: ৳' . $userCredit . ' - ৳' . $orderDelivPrice . ' = ৳' . $userCreditNew . '.';


                //NOTIF INSERT
                NotifService::notifInsert($userId, $orderId, $notifMessage, 'wallet', 'payment', null, null, null,  '0');

                //PAYMENT INSERT WALLET ONLY
                PaymentService::paymentInsert(
                    $userId,
                    $orderId,
                    $orderDelivPrice - $mealboxExtraPrice - $deliveryCommission,
                    'order',
                    'paid',
                    'payment',
                    null,
                    'wallet',
                    null,
                    null,
                    null,
                    null
                );

                $paymentMethod = 'wallet';
            } else {
                //PURPOSE: If credit is LESS than TotalPrice, then pay entirely from wallet or cash. 
                $userCreditNew = 0;
                $paymentMethod = 'cod';
                $cashToCollect = $orderDelivPrice - $userCredit;

                if (($userCredit != 0) && ($userCredit <= $orderDelivPrice)) {
                    //PURPOSE: If credit is more than TotalPrice, then pay from wallet and cash combined. 
                    $notifMessage =  'Paid ৳' . $userCredit . ' from wallet + ৳' . $cashToCollect . ' via cash. New credit: ৳' . $userCreditNew;


                    $totalPrice = ($userCredit + $cashToCollect) - ($mealboxExtraPrice + $deliveryCommission);

                    // Payment from wallet
                    PaymentService::paymentInsert(
                        $userId,
                        $orderId,
                        $userCredit,
                        'order',
                        'paid',
                        'payment',
                        null,
                        'wallet',
                        null,
                        null,
                        null,
                        null
                    );

                    // Payment from cash
                    PaymentService::paymentInsert(
                        $userId,
                        $orderId,
                        $totalPrice,
                        'order',
                        'paid',
                        'payment',
                        null,
                        'cod',
                        null,
                        null,
                        null,
                        null
                    );
                } else {
                    //PURPOSE: If no credit, then pay entirely with cash. 
                    $notifMessage =  'Paid ৳' . $orderDelivPrice . ' via cash';

                    //PAYMENT INSERT CASH ON DELIV
                    PaymentService::paymentInsert(
                        $userId,
                        $orderId,
                        $orderDelivPrice - $mealboxExtraPrice - $deliveryCommission,
                        'order',
                        'paid',
                        'payment',
                        null,
                        'cod',
                        null,
                        null,
                        null,
                        null
                    );
                }


                NotifService::notifInsert($userId, $orderId, $notifMessage, 'wallet', 'payment', null, null, null,  '0');
            }

            //UPDATE WALLET AFTER ORDER PRICE CUT
            $userCreditUpdate = DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->update(['mrd_user_credit' => $userCreditNew]);



            // //PAYMENT INSERT FOR FOOD ONLY
            // PaymentService::paymentInsert(
            //     $userId,
            //     null,
            //     $perMealPrice->mrd_setting_meal_price * $quantity,
            //     'order',
            //     'paid',
            //     'payment',
            //     null,
            //     $paymentMethod,
            //     null,
            //     null,
            //     null,
            //     null
            // );

            //PAYMENT INSERT FOR DELIVERY CHARGE ONLY
            PaymentService::paymentInsert(
                $userId,
                $orderId,
                $deliveryCommission,
                'delivery',
                'paid',
                'payment',
                null,
                $paymentMethod,
                null,
                null,
                null,
                null
            );




            //ORDER DELIV STATUS UPDATE
            $delivUpdate = DB::table('mrd_order')
                ->where('mrd_order_id', $orderId)
                ->update([
                    'mrd_order_status' => $delivStatus,
                    'mrd_order_user_pay_status' => 'paid'
                ]);






            //GET USER INFO



            //INCREMENT/DECREASE MEALBOX COUNT IF USER MEALBOX IS ACTIVATED
            $MealboxService->mealboxHasUpdate($userId, $orderId, $mealboxPicked);



            //MARK: order delivery increment by 1. 
            DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->increment('mrd_user_order_delivered');






            if ($mealboxPicked) {
                $MealboxService->mealboxCashback($userId, $mealboxPicked);
            }


            // Decrease the number of mealboxes based on how many are returned
            // if ($mboxPick > 0 && $user->mrd_user_has_mealbox >= $mboxPick) {

            //     DB::table('mrd_user')
            //         ->where('mrd_user_id', $userId)
            //         ->decrement('mrd_user_has_mealbox', $mboxPick);

            //     // Increase credit by 10 or 20 depending on mboxPick
            //     $creditToAdd = $mboxPick * 10;

            //     DB::table('mrd_user')
            //         ->where('mrd_user_id', $userId)
            //         ->increment('mrd_user_credit', $creditToAdd);





            //     $mrd_notif_message = "You’ve received ৳" . $creditToAdd . " cashback for returning your mealbox.";
            //     // Insert notification for the user
            //     DB::table('mrd_notification')->insert([
            //         'mrd_notif_user_id' => $userId,
            //         'mrd_notif_order_id' => $orderId,
            //         'mrd_notif_message' => $mrd_notif_message,
            //         'mrd_notif_type' => 'cashback',
            //         'mrd_notif_date_added' => now(),
            //     ]);
            // }










            $nextOrder = DB::table('mrd_order')
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_status', 'pending')
                ->orderBy('mrd_order_date', 'asc')
                ->select('mrd_order_id', 'mrd_order_user_id', 'mrd_order_quantity', 'mrd_order_total_price', 'mrd_order_deliv_commission')
                ->first();

            //UPDATE CASH TO GET IF USER HAS A NEXT ORDER
            if (
                $nextOrder
            ) {


                //COLLECT DATA
                $userCreditUpdated = CreditService::userCredit(
                    $userId
                );

                $perMealPrice = SettingsService::perMealPrice();

                $mealboxExtra = $MealboxService->mealboxExtra($nextOrder->mrd_order_user_id,  $nextOrder->mrd_order_quantity);

                $mealboxExtraPrice = $MealboxService->mealboxExtraPrice($mealboxExtra);

                $delivComm = $nextOrder->mrd_order_deliv_commission;

                $nextOrderId = $nextOrder->mrd_order_id;
                $nextOrderQuantity = $nextOrder->mrd_order_quantity;

                //NEXT ORDER TOTAL PRICE

                $nextOrderTotalPrice =  ($nextOrderQuantity * $perMealPrice) +  $mealboxExtraPrice +   $delivComm;



                if (
                    $userCreditUpdated >= $nextOrderTotalPrice
                ) {
                    //$userCreditUpdatedNew = $userCreditUpdated - $nextOrderTotalPrice;
                    $cash_to_get = 0;
                } else {
                    // $userCreditUpdatedNew = 0;
                    $cash_to_get = $nextOrderTotalPrice - $userCreditUpdated;
                }

                // $cash_to_get = $CreditService->cashToGet($userId, $quantity);

                $totalPrice = $CreditService->totalPrice($userId, $quantity);

                //CASH TO GET UPDATE
                $cashToGet = DB::table('mrd_order')
                    ->where('mrd_order_id', $nextOrderId)
                    ->update([
                        'mrd_order_cash_to_get' => $cash_to_get,
                        'mrd_order_total_price' => $nextOrderTotalPrice,
                        'mrd_order_mealbox_extra' =>  $mealboxExtra
                    ]);

                // $updatedRows = OrderService::orderUpdate(
                //     $userId, //userId
                //     $menuId, //menuId
                //     $date,
                //     null,              // $orderFor= food, item
                //     null,               // Order type = default, custom
                //     null,               //Qty
                //     null,               // $orderStatus
                //     null,              // $orderPayStatus
                //     null,              // $orderRating
                //     null,              // $orderFeedback
                //     $MealboxService,
                //     $CreditService
                // );

                //UPDATE NOTIFICATION
                NotifService::notifUpdate($userId, $nextOrderId, null, 'order', null, null, $mealboxExtra, $cash_to_get,  null);
            }
        }
        //DELIVERED END
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
            'notif' => $notifMessage,
            'delivStatus' => $delivStatus,
            'orderId' => $orderId,
            'userId' => $userId,

            'menuId' => $menuId,
        ]);
    }
}
