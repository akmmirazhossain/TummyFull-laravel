<?php
//use App\Http\Controllers\Controller;
namespace App\Http\Controllers;

use App\Http\Controllers\SmsController;

use App\Services\CreditService;
use App\Services\MealboxService;
use App\Services\NotifService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use App\Services\OrderService;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB facade
use Illuminate\Support\Facades\Hash;


class PhoneVerificationController extends Controller
{
    public function verifyPhoneNumber(Request $request)
    {
        //Retrieve the phone number from the request
        //$phoneNumber = '01673692997';
        $phoneNumber = $request->input('phoneNumber');
        $to = $phoneNumber;


        $otp = rand(1000, 9999);
        $hashedOtp = Hash::make($otp);
        // $message = 'Your OTP is ' . $otp . ' ' . date('h:i a d-M-Y', time()) . '.';
        $message = 'Your OTP is ' . $otp;


        $smsController = new SmsController();
        $smsController->sendSms($to, $message);


        // Check phone number
        $user = DB::table('mrd_user')
            ->where('mrd_user_phone', $phoneNumber)
            ->first();

        // If phone number exists
        if ($user) {
            DB::table('mrd_user')
                ->where('mrd_user_phone', $phoneNumber)
                ->update([
                    'mrd_user_last_otp' => $hashedOtp,
                    'mrd_user_otp_expiration' => now()->addMinutes(5), // Set OTP expiration time
                    'mrd_user_otp_attempts' => 0 // Reset OTP attempts
                ]);

            // Return a success message
            return response()->json(['success' => 'Phone number already exists. OTP updated successfully'], 200);
        } else {


            //DISCOUNT PERCENTAGE CALCULATION
            $settings = DB::table('mrd_setting')->select('mrd_setting_meal_price', 'mrd_setting_discount_reg')->first();


            $mealPrice = $settings->mrd_setting_meal_price; // e.g., 120
            $discountPercentage = $settings->mrd_setting_discount_reg; // e.g., 80

            // Calculate the credit value
            $bonus = round(($mealPrice * $discountPercentage) / 100);


            $sessionToken = bin2hex(random_bytes(32));
            // Insert user with calculated credit value
            $userId = DB::table('mrd_user')->insertGetId([
                'mrd_user_type' => 'customer',
                'mrd_user_phone' => $phoneNumber,
                'mrd_user_last_otp' => $hashedOtp,
                'mrd_user_session_token' => $sessionToken,
                'mrd_user_otp_expiration' => now()->addMinutes(5), // Set OTP expiration time
                'mrd_user_otp_attempts' => 0, // Initialize OTP attempts
                'mrd_user_credit' => $bonus, // Set calculated credit
                'mrd_user_date_added' => now(),
            ]);

            // Insert notification only if credit is more than 0
            if ($bonus > 0) {
                DB::table('mrd_notification')->insert([
                    'mrd_notif_user_id' => $userId,
                    'mrd_notif_message' => "🎉 You've received {$bonus} TK as a welcome bonus! Enjoy your discount on your first meal. 🍛",
                    'mrd_notif_type' => 'wallet', // Change this if needed
                ]);


                // Insert discount record in mrd_payment


                PaymentService::paymentInsert(
                    $userId,
                    null,
                    intval($bonus),
                    'registration',
                    'paid',
                    'discount',
                    null,
                    'system',
                    null,
                    null,
                    null,
                    now()
                );
            }



            // Return a success message
            return response()->json([
                'success' => 'Phone number does not exist. New user created and OTP generated successfully',
                'new_user' => 'yes'
            ], 201);
        }
    }


    public function verifyOtp(Request $request)
    {
        // Retrieve the OTP and phone number from the request
        $enteredOtp = $request->input('otp');
        $phone = $request->input('phoneNumber');

        // Query to get the correct OTP, OTP expiration, and attempt count for the given phone number
        $userData = DB::table('mrd_user')
            ->where('mrd_user_phone', $phone)
            ->select('mrd_user_last_otp', 'mrd_user_otp_expiration', 'mrd_user_otp_attempts')
            ->first();


        if (!$userData) {
            // Phone number not found
            return response()->json([
                'status' => 'failed',
                'message' => 'Phone number not found'
            ], 404);
        }


        $correctOtp = $userData->mrd_user_last_otp;
        $otpExpiration = $userData->mrd_user_otp_expiration;
        $otpAttempts = $userData->mrd_user_otp_attempts;



        // Check if OTP has expired
        if ($otpExpiration < now()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'OTP has expired, please try again later.'
            ]);
        }


        // Check if OTP attempts exceed limit (e.g., 3 attempts)
        $otpAttemptsLimit = 5;
        if ($otpAttempts >= $otpAttemptsLimit) {
            // Lock the user's account temporarily
            // You can implement your logic here, such as sending an email to unlock the account
            return response()->json([
                'status' => 'failed',
                'message' => 'Too many failed attempts, please try again later.'
            ]);
        }

        if (Hash::check($enteredOtp, $correctOtp)) {
            // OTP verification successful
            // Reset the OTP attempts count upon successful verification
            DB::table('mrd_user')
                ->where('mrd_user_phone', $phone)
                ->update(['mrd_user_otp_attempts' => 0]);

            // Generate a session token (this is a simple example, you can use JWT or other methods)
            // $sessionToken = bin2hex(random_bytes(32));

            // Store the session token in the database
            $token = DB::table('mrd_user')
                ->where('mrd_user_phone', $phone)
                ->value('mrd_user_session_token');

            // Set the isLoggedInTF cookie
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful!',
                'token' => $token
            ])->cookie('isLoggedInTF', true, 60 * 24 * 60); // Expires in 60 days
        } else {
            // Increment OTP attempts count upon failed verification
            DB::table('mrd_user')
                ->where('mrd_user_phone', $phone)
                ->increment('mrd_user_otp_attempts');

            return response()->json([
                'status' => 'failed',
                'message' => 'Incorrect OTP'
            ]);
        }
    }
}
