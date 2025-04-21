<?php

// namespace App\Services;

// class ResponseService
// {
//     public static function success($data = null, $message = 'Success', $code = 200)
//     {
//         return response()->json([
//             'status' => 'success',
//             'message' => $message,
//             'data' => $data,
//         ], $code);
//     }

//     public static function error($message = 'Something went wrong', $code = 500, $data = null)
//     {
//         return response()->json([
//             'status' => 'error',
//             'message' => $message,
//             'data' => $data,
//         ], $code);
//     }

//     public static function validationError($errors, $message = 'Validation failed', $code = 422)
//     {
//         return response()->json([
//             'status' => 'fail',
//             'message' => $message,
//             'errors' => $errors,
//         ], $code);
//     }
// }
