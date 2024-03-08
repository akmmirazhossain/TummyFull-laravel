<?php

// app/Http/Controllers/SettingController.php

namespace App\Http\Controllers;

use App\Models\SettingMod;
use Carbon\Carbon;

class SettingController extends Controller
{
    public function index()
    {
        // Fetch the mrd_setting record
        $mrdSetting = SettingMod::first();

        // Format time values in AM/PM
        $timeLimitLunch = Carbon::createFromFormat('H:i', $mrdSetting->mrd_setting_time_limit_lunch)->format('h:i a');
        $timeLimitDinner = Carbon::createFromFormat('H:i', $mrdSetting->mrd_setting_time_limit_dinner)->format('h:i a');

        // Create an array to hold the output
        $output = [
            'time_limit_lunch' => $timeLimitLunch,
            'time_limit_dinner' => $timeLimitDinner,
            'delivery_time_lunch' => $mrdSetting->mrd_setting_delivery_time_lunch,
            'delivery_time_dinner' => $mrdSetting->mrd_setting_delivery_time_dinner,
        ];

        return response()->json($output);
    }
}
