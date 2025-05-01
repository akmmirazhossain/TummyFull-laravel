<?php

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Services\CreditService;
use App\Services\MealboxService;
use App\Services\ResponseService;
use App\Services\NotifService;
use App\Services\OrderService;


class MealboxController extends Controller
{



    //MARK: MEALBOX SWTICH
    public function mealboxSwitch(Request $request)
    {

        $switchValue = $request->input("switchValue");
        $TFLoginToken = $request->input("TFLoginToken");

        $NotifService = new NotifService();
        $MealboxService = new MealboxService();
        $CreditService = new CreditService();

        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

        $updatedRows = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->update([
                'mrd_user_mealbox' => $switchValue,
            ]);





        // Get the current date

        //UPDATE MEALBOX STATUS FOR NEXT ORDER IF TURNED ON/OFF
        $today = Carbon::today()->toDateString();

        $lunchLimit = DB::table('mrd_setting')->value('mrd_setting_time_limit_lunch');
        $lunchLimitDateTime = now()->format('Y-m-d') . ' ' . $lunchLimit . ':00';


        $dinnerLimit = DB::table('mrd_setting')->value('mrd_setting_time_limit_dinner');
        $dinnerLimitDateTime = now()->format('Y-m-d') . ' ' . $dinnerLimit . ':00';


        $currentDateTime = now()->format('Y-m-d H:i:s');
        $currentDate = now()->format('Y-m-d');

        //get swtich value
        //if swich value is true, get mealboxGive, set mrd_order_mealbox = quantity

        // $MealboxService->mealboxGive($userId, $quantity);


        // $orderId = DB::table('mrd_order')
        //     ->where('mrd_order_user_id', $userId)
        //     ->where('mrd_menu_period', 'lunch')
        //     ->where('mrd_order_date', $currentDate)
        //     ->value('mrd_order_id');

        // $quantity = DB::table('mrd_order')
        //     ->where('mrd_order_user_id', $userId)
        //     ->where('mrd_menu_period', 'lunch')
        //     ->where('mrd_order_date', $currentDate)
        //     ->value('mrd_order_quantity');


        // if ($currentDateTime < $lunchLimitDateTime) {

        //     DB::table('mrd_order')
        //         ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
        //         ->where('mrd_order.mrd_order_id', $orderId)
        //         ->update([
        //             'mrd_order.mrd_order_mealbox' => $MealboxService->mealboxGive($userId, $quantity)
        //         ]);
        // }


        // if ($currentDateTime < $dinnerLimitDateTime) {

        //     DB::table('mrd_order')
        //         ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
        //         ->where('mrd_order.mrd_order_id', $orderId)
        //         ->update([
        //             'mrd_order.mrd_order_mealbox' => $MealboxService->mealboxGive($userId, $quantity)
        //         ]);
        // }

        // $update = DB::table('mrd_order')
        //     ->where('mrd_order_user_id', $userId)
        //     ->whereDate('mrd_order_date', '>', $today)
        //     ->update(['mrd_order_mealbox' => $switchValue]);


        //UPDATE MEALBOX DETAILS IN ORDER TABLE
        function updateMealbox($userId, $currentDate, $mealPeriod, $cutoffTime, $MealboxService, $currentDateTime)
        {
            if ($currentDateTime < $cutoffTime) {
                $order = DB::table('mrd_order')
                    ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
                    ->where('mrd_order_user_id', $userId)
                    ->where('mrd_menu.mrd_menu_period', $mealPeriod)
                    ->where('mrd_order_date', $currentDate)
                    ->select('mrd_order.mrd_order_id', 'mrd_order.mrd_order_quantity')
                    ->first();

                if ($order) {
                    DB::table('mrd_order')
                        ->where('mrd_order_id', $order->mrd_order_id)
                        ->update([
                            'mrd_order_mealbox' => $MealboxService->mealboxGive($userId, $order->mrd_order_quantity),
                            'mrd_order_mealbox_extra' => $MealboxService->mealboxExtra($userId, $order->mrd_order_quantity)
                        ]);
                }
            }
        }


        updateMealbox($userId, $currentDate, 'lunch', $lunchLimitDateTime, $MealboxService, $currentDateTime);
        updateMealbox($userId, $currentDate, 'dinner', $dinnerLimitDateTime, $MealboxService, $currentDateTime);



        //MARK: INSERT NOTIF
        $NotifService->notifMealbox($userId, $switchValue);




        //GET NOTIF ID OF THE LATEST PENDING ORDER FROM NOW
        $notifData = DB::table('mrd_notification')
            ->join('mrd_order', 'mrd_notification.mrd_notif_order_id', '=', 'mrd_order.mrd_order_id')
            ->where('mrd_notification.mrd_notif_user_id', $userId)
            ->where('mrd_notification.mrd_notif_type', 'order')
            ->where('mrd_order.mrd_order_status', 'pending')
            ->where('mrd_order.mrd_order_date', '>=', now()->toDateString())
            ->orderBy('mrd_notification.mrd_notif_date_added')
            ->select('mrd_notification.mrd_notif_id', 'mrd_notification.mrd_notif_order_id', 'mrd_order.mrd_order_quantity')
            ->first();



        if ($notifData) {

            $notifId = $notifData->mrd_notif_id;
            $orderId = $notifData->mrd_notif_order_id;
            $quantity = $notifData->mrd_order_quantity;


            //UPDATE ORDER DETAILS
            DB::table('mrd_order')
                // ->where('mrd_order_menu_id', $menuId)
                ->where('mrd_order_id', $orderId)
                ->update([
                    'mrd_order_mealbox' =>  $MealboxService->mealboxGive($userId,  $quantity),
                    'mrd_order_mealbox_extra' => $MealboxService->mealboxExtra($userId, $quantity),
                    'mrd_order_total_price' => $CreditService->totalPrice($userId, $quantity),
                    'mrd_order_cash_to_get' => $CreditService->cashToGet($userId, $quantity)
                ]);



            // NOTIFCATION UPDATE FOR PRICE AND MEALBOX EXTRA
            $updateNotif = DB::table('mrd_notification')
                ->where('mrd_notif_id', $notifId)
                // ->orderBy('mrd_notif_date_added', 'desc') // Assuming you have a 'created_at' column for determining the most recent
                ->update([
                    'mrd_notif_total_price' => $CreditService->totalPrice($userId, $quantity),
                    'mrd_notif_mealbox_extra' => $MealboxService->mealboxExtra($userId, $quantity),
                ]);
        }







        return ResponseService::success("Mealbox Switch Triggered.");

        // return ResponseService::success('Mealbox Switch Triggered.', [
        //     'currentTime' => $currentDateTime,
        //     'lunchLimit' => $lunchLimitDateTime,
        // ]);

        // return response()->json([
        //     "success" => true,
        //     "switchValue" => $switchValue,
        //     "currentDateTime" => $currentDateTime,
        //     "lunchLimitDateTime" => $lunchLimitDateTime,
        //     "dinnerLimitDateTime" => $dinnerLimitDateTime,


        // ]);
        // return response()->json($switchValue);
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
