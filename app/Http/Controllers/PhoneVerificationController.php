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
        //$phoneNumber = '01673692997';
        $phoneNumber = $request->input('phoneNumber');
        $to = $phoneNumber;


        $otp = rand(100000, 999999);
        $message = 'You OTP is ' . $otp . ' ' . date('h:i a d-M-Y', time());



        // Insert OTP into database
        // DB::table('mrd_user')->insert([
        //     'mrd_user_phone' => $phoneNumber,
        //     'mrd_user_last_otp' => $otp,
        //     'mrd_user_date_added' => now(),
        // ]);



        // Check phone number
        $user = DB::table('mrd_user')
            ->where('mrd_user_phone', $phoneNumber)
            ->first();

        // If phone number exists
        if ($user) {
            DB::table('mrd_user')
                ->where('mrd_user_phone', $phoneNumber)
                ->update(['mrd_user_last_otp' => $otp]);

            // Return a success message
            //return response()->json(['message' => 'Phone number already exists. OTP updated successfully'], 200);
        } else {

            // If phone number DOES NOT exists, insert user
            DB::table('mrd_user')->insert([
                'mrd_user_phone' => $phoneNumber,
                'mrd_user_last_otp' => $otp,
                'mrd_user_date_added' => now(),
            ]);
        }



        $url = "http://api.greenweb.com.bd/api.php?json";
        $token = "10406160548170211634821be8233e1868988b44de23e322ff166";
        $data = [
            'to' => $to,
            'message' => $message,
            'token' => $token,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $smsResult = curl_exec($ch);


        echo $smsResult;
        echo curl_error($ch);
        curl_close($ch);

        // TODO: Implement your phone number verification logic here
        // For now, let's just return a dummy response
        return response()->json(['message' => 'Phone number verification successful']);
    }


    public function verifyOtp(Request $request)
    {
        // Retrieve the OTP from the request
        $enteredOtp = $request->input('otp');

        // Dummy OTP for verification
        $correctOtp = DB::table('mrd_user')
            ->value('mrd_user_last_otp');

        // Perform the OTP verification logic
        if ($enteredOtp === $correctOtp) {
            // OTP verification successful
            return response()->json([
                'status' => 'success',
                'message' => 'OTP verification successfull',
                'otp' => $correctOtp // Include the OTP in the response
            ]);
        } else {
            // OTP verification failed
            return response()->json([
                'status' => 'failed',
                'message' => 'Incorrect OTP'
            ]);
        }
    }


}
