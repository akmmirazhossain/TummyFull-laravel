<?php

namespace App\Services;

class ResponseService
{
    public static function success($message, $data = [], $code = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (!empty($data) && is_array($data)) {
            $response = array_merge($response, $data);
        }

        return response()->json($response, $code);
    }



    public static function error($message, $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $statusCode);
    }
}
