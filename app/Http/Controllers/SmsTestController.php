<?php

// app/Http/Controllers/SmsTestController.php

namespace App\Http\Controllers;

class SmsTestController extends Controller
{
    public function sendSms()
    {
        $to = "01673692997";
        $token = "10406160548170211634821be8233e1868988b44de23e322ff166";
        $message = "Test SMS using API";

        $url = "http://api.greenweb.com.bd/api.php?json";

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

        // Result
        echo $smsResult;

        // Error Display
        echo curl_error($ch);

        // Close cURL session
        curl_close($ch);
    }
}
