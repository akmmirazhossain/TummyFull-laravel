<?php

// app/Http/Controllers/SettingController.php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeliveryMod;
use App\Models\SettingMod;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SettingController extends Controller
{
    //MARK: SERVER SETT
    public function serverSettings()
    {
        // Fetch the mrd_setting record
        $mrdSetting = SettingMod::first();

        // Format time values in AM/PM
        $timeLimitLunch = Carbon::createFromFormat(
            "H:i",
            $mrdSetting->mrd_setting_time_limit_lunch
        )->format("h:i a");
        $timeLimitDinner = Carbon::createFromFormat(
            "H:i",
            $mrdSetting->mrd_setting_time_limit_dinner
        )->format("h:i a");
        $timeLimitLunch24h = Carbon::createFromFormat(
            "H:i",
            $mrdSetting->mrd_setting_time_limit_lunch
        )->format("Hi");
        $timeLimitDinner24h = Carbon::createFromFormat(
            "H:i",
            $mrdSetting->mrd_setting_time_limit_dinner
        )->format("Hi");

        // Get server time and date
        $serverTime = date("h:i a");
        $serverDate = date("d-M-Y");
        $serverDateOrderAuto = date("D, jS M");

        $serverTime24h = sprintf("%02d%02d", date("H"), date("i"));

        // Create an array to hold the output
        $output = [
            "announcement" => $mrdSetting->mrd_setting_announcement,
            "order_max_days" => $mrdSetting->mrd_setting_order_max_days,
            "time_limit_lunch" => $timeLimitLunch,
            "time_limit_dinner" => $timeLimitDinner,
            "time_limit_lunch_24h" => $timeLimitLunch24h,
            "time_limit_dinner_24h" => $timeLimitDinner24h,
            "delivery_time_lunch" =>
            $mrdSetting->mrd_setting_delivery_time_lunch,
            "delivery_time_dinner" =>
            $mrdSetting->mrd_setting_delivery_time_dinner,
            "delivery_charge" => $mrdSetting->mrd_setting_delivery_charge,
            "quantity_min" => $mrdSetting->mrd_setting_quantity_min,
            "quantity_max" => $mrdSetting->mrd_setting_quantity_max,
            "server_time" => $serverTime,
            "server_time_24h" => $serverTime24h,
            "server_date" => $serverDate,
            "serverDateOrderAuto" => $serverDateOrderAuto,
        ];

        return response()->json($output);
    }

    //MARK: MEALBOX STATUS UPDATER
    public function mealboxSwitch(Request $request)
    {
        $switchValue = $request->input("switchValue");
        $TFLoginToken = $request->input("TFLoginToken");
        $userId = User::where("mrd_user_session_token", $TFLoginToken)->value(
            "mrd_user_id"
        );

        $updatedRows = User::where("mrd_user_id", $userId)
            ->update([
                "mrd_user_mealbox" => $switchValue,
            ]);



        $NotificationController = new NotificationController();
        $orderExistance = $NotificationController->notifMealbox($userId, $switchValue);

        // Get the current date
        $today = Carbon::today()->toDateString();

        // Perform the update using Laravel's DB facade
        $update = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->whereDate('mrd_order_date', '>', $today)
            ->update(['mrd_order_mealbox' => $switchValue]);

        // Check if a row exists
        $exists = DB::table('mrd_delivery')
            ->where('mrd_delivery_user_id', $userId)
            ->where('mrd_delivery_message_type', 'mealbox')
            ->exists();

        if ($exists) {
            // Update existing row
            DB::table('mrd_delivery')
                ->where('mrd_delivery_user_id', $userId)
                ->where('mrd_delivery_message_type', 'mealbox')
                ->update([
                    'mrd_delivery_status' => $switchValue == 1 ? '1' : '0',
                    'mrd_delivery_message' => $switchValue == 1 ? 'mealbox activated' : 'mealbox deactivated'
                ]);
        } else {
            // Insert new row
            DB::table('mrd_delivery')->insert([
                'mrd_delivery_user_id' => $userId,
                'mrd_delivery_message_type' => 'mealbox',
                'mrd_delivery_status' => $switchValue == 1 ? '1' : '0',
                'mrd_delivery_message' => $switchValue == 1 ? 'mealbox activated' : 'mealbox deactivated'
            ]);
        }




        return response()->json([
            "success" => true,
            "switchValue" => $switchValue,


        ]);
        //return response()->json($switchValue);
    }
}
