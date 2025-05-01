<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;


use App\Services\MealboxService;

class NotifService
{

    //MARK: PLACE ORDER
    public function notifOrderPlace($userId, $menuId, $date, $totalPrice, $orderId,  $switchValue, $quantity)
    {



        $menuPeriod = DB::table('mrd_menu')
            ->where('mrd_menu_id', $menuId)
            ->value('mrd_menu_period');

        $MealboxService = new MealboxService();



        $formattedDate = Carbon::parse($date)->format('M j (D)');



        if ($switchValue == 1) {
            $notif_message =  "Ordered " . $menuPeriod . ", " . $formattedDate;
        } else {
            $notif_message =  "Canceled " . $menuPeriod . ", " . $formattedDate;
        }



        $optionalFields = [];

        if ($switchValue == 1) {
            $optionalFields = [
                'mrd_notif_total_price' => $totalPrice,
                'mrd_notif_quantity' => $quantity,
                'mrd_notif_mealbox_extra' => $MealboxService->mealboxExtra($userId, $quantity),
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
                $notif_message =  "Mealbox activated for Tk " . $mealBoxPrice . ".";
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

    public static function notifInsert(
        $userId,
        $orderId,
        $message,
        $type,
        $action,
        $quantity,
        $mboxExtra,
        $totalPrice,
        $seen,

    ) {
        return DB::table('mrd_notification')->insert([
            'mrd_notif_user_id'     => $userId,
            'mrd_notif_order_id'    => $orderId,
            'mrd_notif_message'     => $message,
            'mrd_notif_type'        => $type,
            'mrd_notif_action'        => $action,
            'mrd_notif_quantity'    => $quantity,
            'mrd_notif_mealbox_extra'     => $mboxExtra,
            'mrd_notif_total_price' => $totalPrice,
            'mrd_notif_seen' => $seen,
        ]);
    }


    // MARK: Notification Update
    public static function notifUpdate(
        $userId,
        $orderId,
        $message = null,
        $type,
        $action,
        $quantity = null,
        $mboxExtra = null,
        $totalPrice = null,
        $seen = null
    ) {
        $updateData = [];

        if (!is_null($message)) {
            $updateData['mrd_notif_message'] = $message;
        }

        if (!is_null($quantity)) {
            $updateData['mrd_notif_quantity'] = $quantity;
        }

        if (!is_null($mboxExtra)) {
            $updateData['mrd_notif_mealbox_extra'] = $mboxExtra;
        }

        if (!is_null($totalPrice)) {
            $updateData['mrd_notif_total_price'] = $totalPrice;
        }

        if (!is_null($seen)) {
            $updateData['mrd_notif_seen'] = $seen;
        }

        if (!is_null($action)) {
            $updateData['mrd_notif_action'] = $action;
        }

        if (empty($updateData)) {
            return false; // or throw an exception
        }

        return DB::table('mrd_notification')
            ->where('mrd_notif_user_id', $userId)
            ->where('mrd_notif_order_id', $orderId)
            ->where('mrd_notif_type', $type)
            ->update($updateData);
    }
}
