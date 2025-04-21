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



    //MARK: Mealbox Switch
    public function mealboxSwitch(Request $request)
    {

        $switchValue = $request->input("switchValue");
        $TFLoginToken = $request->input("TFLoginToken");

        $NotifService = new NotifService();

        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

        $updatedRows = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->update([
                'mrd_user_mealbox' => $switchValue,
            ]);





        // Get the current date
        $today = Carbon::today()->toDateString();

        $lunchLimit = DB::table('mrd_setting')->value('mrd_setting_time_limit_lunch');
        $lunchLimitDateTime = now()->format('Y-m-d') . ' ' . $lunchLimit . ':00';


        $dinnerLimit = DB::table('mrd_setting')->value('mrd_setting_time_limit_dinner');
        $dinnerLimitDateTime = now()->format('Y-m-d') . ' ' . $dinnerLimit . ':00';


        $currentDateTime = now()->format('Y-m-d H:i:s');
        $currentDate = now()->format('Y-m-d');

        if ($currentDateTime < $lunchLimitDateTime) {

            DB::table('mrd_order')
                ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
                ->whereDate('mrd_order.mrd_order_date', $currentDate)
                ->where('mrd_menu.mrd_menu_period', 'lunch')
                ->where('mrd_order.mrd_order_user_id', $userId)
                ->update([
                    'mrd_order.mrd_order_mealbox' => $switchValue
                ]);
        }


        if ($currentDateTime < $dinnerLimitDateTime) {

            DB::table('mrd_order')
                ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
                ->whereDate('mrd_order.mrd_order_date', $currentDate)
                ->where('mrd_menu.mrd_menu_period', 'dinner')
                ->where('mrd_order.mrd_order_user_id', $userId)
                ->update([
                    'mrd_order.mrd_order_mealbox' => $switchValue
                ]);
        }

        $update = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->whereDate('mrd_order_date', '>', $today)
            ->update(['mrd_order_mealbox' => $switchValue]);


        $NotifService->notifMealbox($userId, $switchValue);


        return ResponseService::success('Mealbox Switch Triggered.');

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
