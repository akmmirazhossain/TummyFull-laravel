<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MenuController;
use Illuminate\Http\Request;
use App\Http\Controllers\NotificationController;
use \App\Models\OrderMod;
use Illuminate\Support\Facades\DB;
use Exception;


class OrderController extends Controller
{



    //MARK: PLACE ORDER
    public function orderPlace(Request $request)
    {

        $menuController = new MenuController();
        $menuId = $request->input('menuId');
        $date = $request->input('date');
        $price = $request->input('price');
        $TFLoginToken = $request->input('TFLoginToken');
        $switchValue = $request->input('switchValue');
        $quantity = $request->input('quantity');





        // Fetch user ID based on session token
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

        // Check if the user ID is fetched successfully
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve user ID '
            ]);
        }



        // Insert Data if switchValue is true
        if ($switchValue == '1') {

            $orderExistance = $menuController->getOrderStatus($userId, $menuId, $date, 'cancelled');

            //IF ORDER EXISTS
            if ($orderExistance == "enabled") {

                //GET CASH TO GET VALUE
                $userCredit = DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->value('mrd_user_credit');

                $subtotal = $userCredit - $price;

                if ($subtotal > 0) {
                    $cash_to_get = 0;
                } else {

                    $cash_to_get = abs($subtotal);
                }


                //UPDATE ORDER IF ORDER EXISTS
                $updatedRows = DB::table('mrd_order')
                    ->where('mrd_order_menu_id', $menuId)
                    ->where('mrd_order_user_id', $userId)
                    ->where('mrd_order_date', $date)
                    ->update([
                        'mrd_order_total_price' => $price,
                        'mrd_order_mealbox' => $this->getUserMealboxById($userId),
                        'mrd_order_quantity' => $quantity,
                        'mrd_order_cash_to_get' => $cash_to_get,
                        'mrd_order_status' => 'pending'
                    ]);


                $orderId = DB::table('mrd_order')
                    ->where('mrd_order_menu_id', $menuId)
                    ->where('mrd_order_user_id', $userId)
                    ->where('mrd_order_date', $date)
                    ->pluck('mrd_order_id')
                    ->first();


                //INSERT NOTIFICATION
                $notificationController = app(NotificationController::class);

                // Data to send
                $notif_data = compact(
                    'menuId',
                    'date',
                    'price',
                    'orderId',
                    'TFLoginToken',
                    'switchValue',
                    'quantity'
                );

                // Create and send the request
                $result = $notificationController->notifOrderPlace(new Request($notif_data));


                // Return a response based on update result
                if ($updatedRows > 0) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Order updated successfully',
                        'updatedRows' => $updatedRows,
                        'orderId' => $orderId

                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No matching order found to update'
                    ]);
                }
            } else {


                //NEW ORDER, INSERT

                $userCredit = DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->value('mrd_user_credit');



                //CALCULATE CASH TO GET for mrd_order table insert
                $subtotal = $userCredit - $price;

                if ($subtotal > 0) {
                    $cash_to_get = 0;
                } else {

                    $cash_to_get = abs($subtotal);
                }




                //MRD_ORDER INSERT DATA, NEW ORDER
                $orderId = DB::table('mrd_order')->insertGetId([
                    'mrd_order_user_id' => $userId,
                    'mrd_order_menu_id' => $menuId,
                    'mrd_order_quantity' => $quantity,
                    'mrd_order_mealbox' => $this->getUserMealboxById($userId),
                    'mrd_order_total_price' => $price,
                    'mrd_order_cash_to_get' => $cash_to_get,
                    'mrd_order_date' => $date
                ]);

                //INSERT NOTIFICATION
                $notificationController = app(NotificationController::class);

                // Data to send
                $notif_data = compact(
                    'menuId',
                    'date',
                    'price',
                    'orderId',
                    'TFLoginToken',
                    'switchValue',
                    'quantity'
                );

                // Create and send the request
                $result = $notificationController->notifOrderPlace(new Request($notif_data));




                // Return a success response
                return response()->json([
                    'success' => true,
                    'message' => 'Order inserted successfully',
                    'orderId' => $orderId

                ]);
            }
        } else {
            $updatedRows = OrderMod::where(
                'mrd_order_menu_id',
                $menuId
            )
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_date', $date)
                ->update(['mrd_order_status' => 'cancelled']);

            $orderId = DB::table('mrd_order')
                ->where('mrd_order_menu_id', $menuId)
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_date', $date)
                ->pluck('mrd_order_id')
                ->first();


            //INSERT NOTIFICATION
            $notificationController = app(NotificationController::class);

            // Data to send
            $notif_data = compact(
                'menuId',
                'date',
                'price',
                'orderId',
                'TFLoginToken',
                'switchValue',
                'quantity'
            );

            // Create and send the request
            $result = $notificationController->notifOrderPlace(new Request($notif_data));



            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'updatedRows' => $updatedRows

            ]);
        }
    }

    //MARK: quantityChng
    public function quantityChanger(Request $request)
    {

        //$menuController = new MenuController();
        // Retrieve data from the request
        $menuId = $request->input('menuId');
        $date = $request->input('date');
        $TFLoginToken = $request->input('TFLoginToken');
        $quantityValue = $request->input('quantityValue');
        $totalPrice = $request->input('totalPrice');


        // Fetch user ID based on session token
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');


        if ($userId) {


            $userCredit = DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->value('mrd_user_credit');

            $subtotal = $userCredit - $totalPrice;

            if ($subtotal > 0) {
                $cash_to_get = 0;
            } else {

                $cash_to_get = abs($subtotal);
            }


            //UPDATE ORDER WITH NEW QUANTITY
            $updatedRows = DB::table('mrd_order')
                ->where('mrd_order_menu_id', $menuId)
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_date', $date)
                ->update([
                    'mrd_order_quantity' => $quantityValue,
                    'mrd_order_total_price' => $totalPrice,
                    'mrd_order_cash_to_get' => $cash_to_get
                ]);





            $orderId = DB::table('mrd_order')
                ->where('mrd_order_menu_id', $menuId)
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_date', $date)
                ->pluck('mrd_order_id')
                ->first();


            // NOTIFCATION UPDATE FOR QUANTITY
            $updateNotif = DB::table('mrd_notification')
                ->where('mrd_notif_order_id', $orderId)
                ->orderBy('mrd_notif_date_added', 'desc') // Assuming you have a 'created_at' column for determining the most recent
                ->limit(1)
                ->update([
                    'mrd_notif_quantity' => $quantityValue,
                    'mrd_notif_total_price' => $totalPrice,
                ]);





            return response()->json([
                'success' => true,
                'message' => 'Quantity has been changed',
                'orderId' => $orderId,
                //'data' => $menuId, $date, $TFLoginToken, $quantityValue


            ]);
        }
    }

    //MARK: Mbox stat
    public function getUserMealboxById($id)
    {
        // Execute the query to fetch the mrd_user_mealbox value
        $mealboxValue = DB::table('mrd_user')
            ->where('mrd_user_id', $id)
            ->value('mrd_user_mealbox');

        return $mealboxValue;
    }

    //MARK: Mbox Stat API
    public function mealboxStatApi(Request $request)
    {

        $TFLoginToken = $request->input('TFLoginToken');

        // Fetch user ID based on session token
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');
        // Execute the query to fetch the mrd_user_mealbox value
        $mealboxValue = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_mealbox');

        return $mealboxValue;
    }
}
