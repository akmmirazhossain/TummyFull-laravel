<?php

namespace App\Http\Controllers;





use Illuminate\Http\Request;

use \App\Models\OrderMod;
use App\Services\CreditService;
use App\Services\MealboxService;
use App\Services\ResponseService;
use App\Services\NotifService;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;



use Exception;





class OrderController extends Controller
{



    //MARK:ORDER PLACE 
    public function orderPlace(Request $request)
    {



        $date = $request->input('date');
        $menuId = $request->input('menuId');
        $TFLoginToken = $request->input('TFLoginToken');
        $switchValue = $request->input('switchValue');
        $quantity = $request->input('quantity');
        $orderType = $request->input('orderType');
        $selectedFoods = $request->input('selectedFoods');



        $CreditService = new CreditService();
        $NotifService = new NotifService();
        $OrderService = new OrderService();
        $MealboxService = new MealboxService();




        //GET SETTINGS TABLE DATA

        $pricePerMeal = DB::table('mrd_setting')->value('mrd_setting_meal_price');


        // Fetch user ID based on session token
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');



        $totalPrice = $CreditService->totalPrice($userId, $quantity);


        // Check if the user ID is fetched successfully
        if (!$userId) {
            return ResponseService::error('Unable to retrieve user ID');
        }




        // MARK: SWITCH IS TRUE
        if ($switchValue == '1') {

            $orderExistance = $OrderService->getOrderStatus($userId, $menuId, $date, 'cancelled');

            //MARK: ORDER EXIST
            if ($orderExistance == "enabled") {

                //IF THE ORDER EXISTS THEN PERFROM THESE FUNCTIONS

                $cashToGet = $CreditService->cashToGet($userId, $quantity);

                //UPDATE ORDER IF ORDER EXISTS
                $updatedRows = DB::table('mrd_order')
                    ->where('mrd_order_menu_id', $menuId)
                    ->where('mrd_order_user_id', $userId)
                    ->where('mrd_order_date', $date)
                    ->update([
                        'mrd_order_total_price' => $pricePerMeal,
                        'mrd_order_mealbox' => $MealboxService->extraMealbox($userId, $quantity),
                        'mrd_order_quantity' => $quantity,
                        'mrd_order_cash_to_get' => $cashToGet,
                        'mrd_order_status' => 'pending'
                    ]);


                //GET ORDER ID
                $orderId = DB::table('mrd_order')
                    ->where('mrd_order_menu_id', $menuId)
                    ->where('mrd_order_user_id', $userId)
                    ->where('mrd_order_date', $date)
                    ->pluck('mrd_order_id')
                    ->first();



                $NotifService->notifOrderPlace($userId, $menuId, $date, $totalPrice, $orderId,  $switchValue, $quantity);


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

                //MARK: ORDER NEW 
                $cash_to_get = $CreditService->cashToGet($userId, $quantity);
                $totalPrice = $CreditService->totalPrice($userId, $quantity);


                //MRD_ORDER INSERT DATA, NEW ORDER
                $orderId = DB::table('mrd_order')->insertGetId([
                    'mrd_order_user_id' => $userId,
                    'mrd_order_menu_id' => $menuId,
                    'mrd_order_quantity' => $quantity,
                    'mrd_order_type' => $orderType,
                    'mrd_order_mealbox' => $MealboxService->extraMealbox($userId, $quantity),
                    'mrd_order_total_price' => $totalPrice,
                    'mrd_order_cash_to_get' => $cash_to_get,
                    'mrd_order_date' => $date
                ]);

                //if order type custom
                if ($orderType === 'custom' && is_array($selectedFoods)) {
                    foreach ($selectedFoods as $foodId) {
                        DB::table('mrd_order_custom')->insert([
                            'mrd_order_cus_order_id' => $orderId,
                            'mrd_order_cus_item_id' => $foodId,
                            'mrd_order_cus_date_update' => now(),
                        ]);
                    }
                }


                $NotifService->notifOrderPlace($userId, $menuId, $date, $totalPrice, $orderId,  $switchValue, $quantity);



                //MARK: JSON RES
                return response()->json([
                    'success' => true,
                    'totalPrice' => $totalPrice,
                    'cash_to_get' => $cash_to_get,
                    'message' => 'Order inserted successfully',
                    'selectedFoods' => $selectedFoods,
                    'orderType' => $orderType,
                    'orderId' => $orderId

                ]);
            }
        } else {

            //MARK: ORDER CANCEL 
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



            $NotifService->notifOrderPlace($userId, $menuId, $date, $totalPrice, $orderId,  $switchValue, $quantity);


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

        // Retrieve data from the request
        $menuId = $request->input('menuId');
        $date = $request->input('date');
        $TFLoginToken = $request->input('TFLoginToken');
        $quantity = $request->input('quantityValue');

        $CreditService = new CreditService();
        $NotifService = new NotifService();
        $OrderService = new OrderService();
        $MealboxService = new MealboxService();

        // Fetch user ID based on session token
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');


        if ($userId) {

            $cash_to_get = $CreditService->cashToGet($userId, $quantity);
            $totalPrice = $CreditService->totalPrice($userId, $quantity);
            $extraBox = $MealboxService->extraMealbox($userId, $quantity);

            //UPDATE ORDER WITH NEW QUANTITY & MEALBOX
            DB::table('mrd_order')
                ->where('mrd_order_menu_id', $menuId)
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_date', $date)
                ->update([
                    'mrd_order_quantity' => $quantity,
                    'mrd_order_mealbox' =>  $extraBox,
                    'mrd_order_total_price' => $totalPrice,
                    'mrd_order_cash_to_get' => $cash_to_get
                ]);



            //GET ORDER ID
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
                    'mrd_notif_quantity' => $quantity,
                    'mrd_notif_total_price' => $totalPrice,
                ]);





            return response()->json([
                'success' => true,
                'message' => 'Quantity has been changed',
                'orderId' => $orderId,
                'extraBox' => $extraBox,
                //'data' => $menuId, $date, $TFLoginToken, $quantity


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
}
