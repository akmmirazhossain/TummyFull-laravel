<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

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
                'mrd_user.mrd_user_phone',
                'mrd_user.mrd_user_id'
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

                    $this->sendSms($order->mrd_user_phone, $message);

                    $userId = $order->mrd_order_user_id; // Fetch user ID
                    $this->insertSms($userId, $order->mrd_user_phone, $message, 'discount');
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

                    $this->sendSms($order->mrd_user_phone, $message);

                    $userId = $order->mrd_order_user_id; // Fetch user ID
                    $this->insertSms($userId, $order->mrd_user_phone, $message, 'discount');
                }
            }
        }
    }

    //THIS FUNCTON SENDS SMS TO THE NEW USER WHOSE DELIVERED ORDER IS 1 ON THE SAME DAY
    //CRON TIME PLANNING 3 PM & 10 PM
    public function smsDiscountNewUser()
    {


        $settings = DB::table('mrd_setting')->first();

        //DEACTIVATE SMS IF NULL OR 0 
        if (!$settings || $settings->mrd_setting_discount_new_user == 0 || is_null($settings->mrd_setting_discount_new_user)) {
            return response()->json(['message' => 'Discount is not available.']);
        }
        // Check if mrd_user_order_delivered is 1 to 7 (mealLimit)
        $mealLimit = $settings->mrd_setting_discount_new_user_limit; // Fetch the dynamic meal limit

        $users = DB::table('mrd_user')
            ->whereBetween('mrd_user_order_delivered', [1, $mealLimit]) // Use dynamic limit
            ->pluck('mrd_user_id');

        if ($users->isEmpty()) {
            return response()->json(['message' => 'Step 1: No eligible users found.']);
        }

        $today = Carbon::today();

        // Find the first order date of each user
        $userOrders = DB::table('mrd_order')
            ->whereIn('mrd_order_user_id', $users)
            ->select('mrd_order_user_id', DB::raw('MIN(mrd_order_date_insert) as first_order_date'))
            ->groupBy('mrd_order_user_id')
            ->get();


        //CHECK IF THAT FIRST ORDER WAS TODAY
        // $userOrders = DB::table('mrd_order')
        //     ->whereIn('mrd_order_user_id', $users)
        //     ->whereDate('mrd_order_date_insert', '=', $today)
        //     ->select('mrd_order_user_id', DB::raw('MIN(mrd_order_date_insert) as first_order_date'))
        //     ->groupBy('mrd_order_user_id', 'mrd_order_date_insert') // Group by both user and date
        //     ->get();

        if ($userOrders->isEmpty()) {
            return response()->json(['message' => 'Step 2: No orders found for eligible users.']);
        }


        $userPhones = DB::table('mrd_user')
            ->whereIn('mrd_user_id', $users)
            ->pluck('mrd_user_phone', 'mrd_user_id');
        // $settings = DB::table('mrd_setting')->first();

        //CALUCLATE DISCOUNT PERCENTAGE FOR THE NEW USER
        $discountAmount = ($settings->mrd_setting_discount_new_user / 100) * $settings->mrd_setting_meal_price;
        $dayLimit = $settings->mrd_setting_discount_new_user_day_limit;
        $newUserDiscount = $settings->mrd_setting_discount_new_user;


        // Filter users whose first order was within the last 14 days
        $eligibleUsers = $userOrders->filter(function ($order) use ($today, $dayLimit) {
            return Carbon::parse($order->first_order_date)->diffInDays($today) <=  $dayLimit;
        })->pluck('mrd_order_user_id');


        //SEND SMS TO THE NEW USERS IF THEY HAVE DISCOUNT
        foreach ($userOrders->whereIn('mrd_order_user_id', $eligibleUsers) as $order) {
            $userPhone = $userPhones[$order->mrd_order_user_id] ?? null; // Get the phone number
            $userId = $order->mrd_order_user_id ?? null;

            $message = "Congrats! 🎉💰 " . intval($discountAmount) . " TK credited to your wallet! Get " . $newUserDiscount . "% off on your next " . $mealLimit . " meals! Order within " . $dayLimit . " days! 🍽️";


            echo "User ID: {$order->mrd_order_user_id}, Phone: {$userPhone}, Message: {$message} <br>";

            $this->insertSms($userId, $userPhone, $message, 'discount');
        }

        // foreach ($userOrders as $order) {
        //     $userPhone = $userPhones[$order->mrd_order_user_id];  // Get the phone number
        //     $message = "Congrats! 🎉💰 60 TK credited to your wallet! Get 50% off on your next 7 meals! Order within 14 days! 🍽️";
        //     $response = $this->sendSms($userPhone, $message); // Send SMS
        // }


        // return response()->json(['sms_response' => $response]);
    }

    public function insertSms($userId, $phone, $message, $type, $status = 'sent')
    {
        return DB::table('mrd_sms')->insert([
            'mrd_sms_user_id' => $userId,
            'mrd_sms_phone' => $phone,
            'mrd_sms_message' => $message,
            'mrd_sms_status' => $status,
            'mrd_sms_type' => $type,
            'mrd_sms_date_sent' => now()
        ]);
    }



    function sendSms($to, $message)
    {
        $url = "http://api.greenweb.com.bd/api.php?json";
        $token = "10406160548170211634821be8233e1868988b44de23e322ff166";

        $data = [
            'to'      => $to,
            'message' => $message,
            'token'   => $token,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $smsResult = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "SMS Error: " . $error;
        }

        return json_decode($smsResult, true); // Decode response if needed
    }
}
