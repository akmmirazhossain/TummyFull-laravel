<?php
//use App\Http\Controllers\Controller;
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB facade

// use Illuminate\Support\Facades\Auth;
// use App\Models\User;
// use Illuminate\Support\Facades\Hash;




class PhoneVerificationController extends Controller
{
    public function verifyPhoneNumber(Request $request)
    {
        //Retrieve the phone number from the request
        $phoneNumber = '01673692997';
        //$phoneNumber = $request->input('phoneNumber');
        $to = $phoneNumber;
        $token = "10406160548170211634821be8233e1868988b44de23e322ff166";
        $message = date('h:i a d-M-Y', time());
        $otp = rand(100000, 999999);


        // Insert OTP into database
        DB::table('mrd_user')->insert([
            'mrd_user_phone' => $phoneNumber,
            'mrd_user_last_otp' => $otp,
            'mrd_user_date_added' => now(),
            // Add any additional fields you want to insert into the table
        ]);

        // Check if the phone number already exists in the mrd_user_last_otp table
        $userOtp = MrdUserLastOtp::where('phone_number', $phoneNumber)->first();

        // If the phone number exists, update the OTP
        if ($userOtp) {
            $userOtp->otp = $otp;
            $userOtp->save();
        } else {
            // If the phone number does not exist, create a new record
            MrdUserLastOtp::create([
                'phone_number' => $phoneNumber,
                'otp' => $otp,
            ]);
        }


        $url = "http://api.greenweb.com.bd/api.php?json";

        $data = [
            'to' => $to,
            'message' => $message,
            'token' => $token,
        ];

        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $url);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        // curl_setopt($ch, CURLOPT_ENCODING, '');
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // $smsResult = curl_exec($ch);


        //echo $smsResult;


        //echo curl_error($ch);


        //curl_close($ch);

        // TODO: Implement your phone number verification logic here
        // For now, let's just return a dummy response
        return response()->json(['message' => 'Phone number verification successful']);
    }


    public function verifyOtp(Request $request)
    {
        // Retrieve the phone number and OTP from the request

        $enteredOtp = $request->input('otp');

        // Perform the OTP verification logic here
        // For simplicity, let's assume the correct OTP is '123456'
        $correctOtp = '123456';

        if ($enteredOtp === $correctOtp) {
            // OTP is correct, you can implement additional logic here
            return response()->json(['status' => 'success', 'message' => 'OTP verification successful']);
        } else {
            // OTP is incorrect
            return response()->json(['status' => 'failed', 'message' => 'Incorrect OTP']);
        }
    }
}
