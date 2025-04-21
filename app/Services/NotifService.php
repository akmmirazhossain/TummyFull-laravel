<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;




class NotifService
{

    //MARK: PLACE ORDER
    public function notifOrderPlace($userId, $menuId, $date, $totalPrice, $orderId,  $switchValue, $quantity)
    {



        $menuPeriod = DB::table('mrd_menu')
            ->where('mrd_menu_id', $menuId)
            ->value('mrd_menu_period');



        $formattedDate = Carbon::parse($date)->format('F j (l)');



        if ($switchValue == 1) {
            $notif_message =  "Ordered " . $menuPeriod . " of " . $formattedDate;
        } else {
            $notif_message =  "Canceled " . $menuPeriod . " of " . $formattedDate;
        }



        $optionalFields = [];

        if ($switchValue == 1) {
            $optionalFields = [
                'mrd_notif_total_price' => $totalPrice,
                'mrd_notif_quantity' => $quantity,
            ];
        }

        $notifInsert = DB::table('mrd_notification')->insert(array_merge([
            'mrd_notif_user_id' => $userId,
            'mrd_notif_message' => $notif_message,
            'mrd_notif_order_id' => $orderId,
            'mrd_notif_type' => 'order',
        ], $optionalFields));


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

        $mealboxIfPaid = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_mealbox_paid');

        if (
            $switchValue == 1
        ) {
            // $notif_message =  "Activated mealbox for TK " . $mealBoxPrice . ".";

            if ($mealboxIfPaid == 1) {
                $notif_message =  "Mealbox reactivated.";
            } else {
                $notif_message =  "Mealbox activated for Tk " . $mealBoxPrice . ". Your upcoming meals will be delivered in a mealbox.";
            }
        } else {

            if ($mealboxIfPaid == 1) {
                $notif_message =  "Mealbox deactivated. Tk " . $mealBoxPrice . " will be refunded, and your current mealbox will be collected.";
            } else {
                $notif_message =  "Mealbox deactivated.";
            }
        }


        $mealboxStat = DB::table('mrd_notification')->insert([
            'mrd_notif_user_id' =>
            $userId,
            'mrd_notif_message' => $notif_message,
            'mrd_notif_type' => 'mealbox'
        ]);
    }
}
