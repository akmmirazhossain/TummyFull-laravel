<?php

// app/Http/Controllers/SettingController.php

namespace App\Http\Controllers;

use App\Models\User;

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
            // "mrd_setting_meal_price" => $mrdSetting->mrd_setting_meal_price,
            "mealbox_price" => $mrdSetting->mrd_setting_mealbox_price,
            // "mrd_setting_commission_chef" => $mrdSetting->mrd_setting_commission_chef,
            "mrd_setting_commission_delivery" => $mrdSetting->mrd_setting_commission_delivery,
            // "mrd_setting_commission_supplier" => $mrdSetting->mrd_setting_commission_supplier,
            // "order_max_days" => $mrdSetting->mrd_setting_order_max_days,
            "time_limit_lunch" => $timeLimitLunch,
            "time_limit_dinner" => $timeLimitDinner,
            "time_limit_lunch_24h" => $timeLimitLunch24h,
            "time_limit_dinner_24h" => $timeLimitDinner24h,
            "delivery_time_lunch" =>
            $mrdSetting->mrd_setting_delivery_time_lunch,
            "delivery_time_dinner" =>
            $mrdSetting->mrd_setting_delivery_time_dinner,
            "mrd_setting_mealbox_enforce_limit" =>
            $mrdSetting->mrd_setting_mealbox_enforce_limit,
            // "quantity_min" => $mrdSetting->mrd_setting_quantity_min,
            // "quantity_max" => $mrdSetting->mrd_setting_quantity_max,
            "server_time" => $serverTime,
            "server_time_24h" => $serverTime24h,
            "server_date" => $serverDate,
            "serverDateOrderAuto" => $serverDateOrderAuto,
        ];

        return response()->json($output);
    }
}
