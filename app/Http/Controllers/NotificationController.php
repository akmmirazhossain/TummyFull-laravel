<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;




class NotificationController extends Controller
{
    //MARK: PLACE ORDER
    public function notifOrderPlace(Request $request)
    {
        $menuId = $request->input('menuId');
        $date = $request->input('date');
        $price = $request->input('price');
        $orderId = $request->input('orderId');
        $TFLoginToken = $request->input('TFLoginToken');
        $switchValue = $request->input('switchValue');
        $quantity = $request->input('quantity');


        // Fetch user ID based on session token
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');


        $userCredit = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_credit');

        $menuPrice = DB::table('mrd_menu')
            ->where('mrd_menu_id', $menuId)
            ->value('mrd_menu_price');

        $menuPeriod = DB::table('mrd_menu')
            ->where('mrd_menu_id', $menuId)
            ->value('mrd_menu_period');



        $formattedDate = Carbon::parse($date)->format('F j (l)');



        if ($switchValue == 1) {
            $notif_message =  "Ordered " . $menuPeriod . " of " . $formattedDate;
        } else {
            $notif_message =  "Canceled " . $menuPeriod . " of " . $formattedDate;
        }




        $notifInsert = DB::table('mrd_notification')->insert([
            'mrd_notif_user_id' =>
            $userId,
            'mrd_notif_message' => $notif_message,
            'mrd_notif_order_id' => $orderId,
            'mrd_notif_total_price' => $price,
            'mrd_notif_type' => 'order',
            'mrd_notif_quantity' => $quantity,
        ]);

        // $creditSum = $userCredit + $price;

        // if ($creditSum < 0) {

        //     echo "The sum is negative.";

        //     $notif_message =  "Ordered " . $menuPeriod . " of " . $formattedDate;
        // }




        return response()->json([
            'success' => true,
            'message' => 'Notification added',
            // 'orderId' => $orderId
        ]);
    }



    //MARK: NOTIF MEALBOX (BUTTON TRIGGER)
    public function notifMealbox($userId, $switchValue)
    {

        $mealBoxPrice = DB::table('mrd_setting')
            ->value('mrd_setting_mealbox_price');

        if (
            $switchValue == 1
        ) {
            $notif_message =  "Activated mealbox for TK " . $mealBoxPrice . ".";
        } else {
            $notif_message =  "Deactivated mealbox, Tk " . $mealBoxPrice . " will be refunded.";
        }


        $mealboxStat = DB::table('mrd_notification')->insert([
            'mrd_notif_user_id' =>
            $userId,
            'mrd_notif_message' => $notif_message,
            'mrd_notif_type' => 'mealbox'
        ]);
    }

    //MARK: GET NOTIF
    public function notifGet(Request $request)
    {
        $TFLoginToken =
            $request->header('Authorization');
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

        $notifications = DB::select("SELECT mrd_notif_message,mrd_notif_date_added,mrd_notif_quantity,mrd_notif_total_price,mrd_notif_seen,mrd_notif_type FROM mrd_notification WHERE mrd_notif_user_id = $userId ORDER BY mrd_notif_id DESC LIMIT 100");

        return response()->json([

            'notifications' =>   $notifications
        ]);
    }


    //MARK: NOTIF SEEN
    public function notifSeen(Request $request)
    {
        $TFLoginToken =
            $request->header('Authorization');
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

        $notif_seen = DB::table('mrd_notification')
            ->where('mrd_notif_user_id', $userId)
            ->where('mrd_notif_seen', 0) // Only update rows where mrd_notif_seen is 0
            ->update(['mrd_notif_seen' => 1]);

        return response()->json([

            'notif_seen' =>   'true'
        ]);
    }

    //MARK: Mbox stat
    public function getUserMealboxById($id) {}

    //MARK: Mbox Stat API
    public function mealboxStatApi(Request $request) {}
}
