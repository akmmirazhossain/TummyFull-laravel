<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MenuController;
use Illuminate\Http\Request;

use \App\Models\OrderMod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;


class OrderController extends Controller
{
    //MARK: PLACE ORDER
    public function orderPlace(Request $request)
    {



        try {




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
            if ($switchValue == 'true') {

                $orderExistance = $menuController->getOrderStatus($userId, $menuId, $date, 'cancelled');

                if ($orderExistance == "enabled") {

                    //UPDATE ORDER IF ORDER EXISTS
                    $updatedRows = OrderMod::where(
                        'mrd_order_menu_id',
                        $menuId
                    )
                        ->where('mrd_order_user_id', $userId)
                        ->where('mrd_order_date', $date)
                        ->update([
                            'mrd_order_total_price' => $price,
                            'mrd_order_mealbox' => $this->getUserMealboxById($userId),
                            'mrd_order_quantity' => $quantity,
                            'mrd_order_status' => 'pending'
                        ]);

                    $orderId = DB::table('mrd_order')
                        ->where('mrd_order_menu_id', $menuId)
                        ->where('mrd_order_user_id', $userId)
                        ->where('mrd_order_date', $date)
                        ->pluck('mrd_order_id')
                        ->first();


                    //UPDATE PAYMENT IF PAYMENT EXISTS
                    $userCreditUpdate = DB::table('mrd_payment')
                        ->where('mrd_payment_order_id', $orderId)
                        ->update(['mrd_payment_amount' => $price]);


                    // Return a response based on update result
                    if ($updatedRows > 0) {
                        return response()->json([
                            'success' => true,
                            'message' => 'Order updated successfully',
                            'updatedRows' => $updatedRows
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => 'No matching order found to update'
                        ]);
                    }
                } else {
                    //MRD_ORDER INSERT DATA, NEW ORDER
                    $order = new OrderMod();
                    $order->mrd_order_user_id = $userId; // Replace $TFLoginToken with $userId
                    $order->mrd_order_menu_id = $menuId;
                    $order->mrd_order_quantity = $quantity;
                    $order->mrd_order_mealbox = $this->getUserMealboxById($userId);
                    $order->mrd_order_total_price = $price;
                    $order->mrd_order_date = $date;
                    $order->save();




                    $orderId = DB::table('mrd_order')
                        ->where('mrd_order_menu_id', $menuId)
                        ->where('mrd_order_user_id', $userId)
                        ->where('mrd_order_date', $date)
                        ->pluck('mrd_order_id')
                        ->first();

                    // //PAYMENT INSERT ON NEW ORDER
                    $paymentInsert = DB::table('mrd_payment')->insert([
                        'mrd_payment_user_id' =>
                        $userId,
                        'mrd_payment_order_id' =>
                        $orderId,
                        'mrd_payment_for' =>
                        'order',
                        'mrd_payment_amount' =>
                        $price
                    ]);




                    // Return a success response
                    return response()->json([
                        'success' => true,
                        'message' => 'Order inserted successfully',
                        'orderId' => $orderId,
                        'data' => $order
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

                return response()->json([
                    'success' => true,
                    'message' => 'Order cancelled successfully',
                    'updatedRows' => $updatedRows

                ]);
            }
        } catch (Exception $e) {
            // Log the error
            logger()->error('Order processing error: ' . $e->getMessage());

            // Return an error response with JSON
            return response()->json(['error' => $e->getMessage()], 500);
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
            $updatedRows = DB::table('mrd_order')
                ->where('mrd_order_menu_id', $menuId)
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_date', $date)
                ->update([
                    'mrd_order_quantity' => $quantityValue,
                    'mrd_order_total_price' => $totalPrice
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
                ->update([
                    'mrd_notif_quantity' => $quantityValue,
                    'mrd_notif_total_price' => $totalPrice,
                ]);


            //UPDATE PAYMENT IF PAYMENT EXISTS

            $userCreditUpdate = DB::table('mrd_payment')
                ->where('mrd_payment_order_id', $orderId)
                ->update(['mrd_payment_amount' => $totalPrice]);




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
