<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SmsController extends Controller
{
    //MARK: USER FETCH
    public function smsOrderFinalAlert(Request $request)
    {

        // Check if SMS reminders are active
        $smsReminderActive = DB::table('mrd_setting')
            ->value('mrd_setting_sms_reminder_active');

        if ($smsReminderActive !== 'yes') {
            return; // Exit the function if reminders are not active
        }


        $helpline = DB::table('mrd_setting')
            ->value('mrd_setting_helpline');

        // echo $lunchTimeLimit . '<br><br>';

        $todayOrders = DB::table('mrd_order')
            ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->join('mrd_user', 'mrd_order.mrd_order_user_id', '=', 'mrd_user.mrd_user_id') // Join with mrd_user table
            ->where('mrd_order.mrd_order_status', 'pending')
            ->whereDate('mrd_order.mrd_order_date', Carbon::today())
            ->select(
                'mrd_order.mrd_order_id',
                'mrd_order.mrd_order_quantity',
                'mrd_order.mrd_order_total_price',
                'mrd_menu.mrd_menu_period',
                'mrd_user.mrd_user_phone'
            )
            ->get();


        // Fetch settings for lunch and dinner delivery times and time limits
        // Fetch settings for lunch and dinner delivery times and time limits
        $settings = DB::table('mrd_setting')
            ->select(
                'mrd_setting_delivery_time_lunch',
                'mrd_setting_delivery_time_dinner',
                'mrd_setting_time_limit_lunch',
                'mrd_setting_time_limit_dinner'
            )
            ->first();

        // Current time for comparisons
        $currentTime = Carbon::now();



        // Loop through today's orders to generate reminders
        foreach ($todayOrders as $order) {
            // Check if it's before lunch limit to only send lunch reminders
            if ($currentTime->lessThanOrEqualTo(Carbon::createFromTimeString($settings->mrd_setting_time_limit_lunch))) {
                if ($order->mrd_menu_period === 'lunch') {
                    $message = "(dalbhath.com) 🍛 You have a lunch order today. Qty: {$order->mrd_order_quantity}, Price: ৳{$order->mrd_order_total_price}"; // Include phone number

                    echo $message . "<br>";

                    $url = "http://api.greenweb.com.bd/api.php?json";
                    $token = "10406160548170211634821be8233e1868988b44de23e322ff166";
                    $data = [
                        'to' => $order->mrd_user_phone,
                        'message' => $message,
                        'token' => $token,
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt(
                        $ch,
                        CURLOPT_SSL_VERIFYPEER,
                        0
                    );
                    curl_setopt($ch, CURLOPT_ENCODING, '');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $smsResult = curl_exec($ch);

                    //echo $smsResult;
                    echo curl_error($ch);
                    curl_close($ch);
                }
            }
            // Check if it's after lunch limit but before dinner limit to only send dinner reminders
            elseif (
                $currentTime->greaterThan(Carbon::createFromTimeString($settings->mrd_setting_time_limit_lunch)) &&
                $currentTime->lessThanOrEqualTo(Carbon::createFromTimeString($settings->mrd_setting_time_limit_dinner))
            ) {
                if ($order->mrd_menu_period === 'dinner') {

                    $message = "(dalbhath.com) 🍛 You have a dinner order today. Qty: {$order->mrd_order_quantity}, Price: ৳{$order->mrd_order_total_price}"; // Include phone number

                    echo $message . "<br>";

                    $url = "http://api.greenweb.com.bd/api.php?json";
                    $token = "10406160548170211634821be8233e1868988b44de23e322ff166";
                    $data = [
                        'to' => $order->mrd_user_phone,
                        'message' => $message,
                        'token' => $token,
                    ];

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt(
                        $ch,
                        CURLOPT_SSL_VERIFYPEER,
                        0
                    );
                    curl_setopt($ch, CURLOPT_ENCODING, '');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $smsResult = curl_exec($ch);

                    //echo $smsResult;
                    echo curl_error($ch);
                    curl_close($ch);
                }
            }
        }
    }
}
