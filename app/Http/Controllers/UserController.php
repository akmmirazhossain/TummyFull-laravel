<?php

namespace App\Http\Controllers;

use App\Models\User;

use App\Models\SettingMod;

use Illuminate\Http\Request;
use App\Models\OrderMod;


class UserController extends Controller
{
    //MARK: USER FETCH
    public function userFetch(Request $request)
    {
        // Retrieve the token from the Authorization header
        $token = $request->header('Authorization');

        // Remove the "Bearer " prefix from the token
        if ($token && strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7); // Extract the token by removing "Bearer "
        }

        // Check if the token is provided
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token not provided',
            ], 401);
        }

        // Fetch user from the database using the token
        $user = User::where('mrd_user_session_token', $token)->first();

        // Check if user was found
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token or user not found',
            ], 401);
        }

        // Return the required user details in the response
        return response()->json([
            'status' => 'success',
            'data' => [
                'first_name' => $user->mrd_user_first_name,
                'phone' => $user->mrd_user_phone,
                'address' => $user->mrd_user_address,
                'email' => $user->mrd_user_email,
                'mrd_user_mealbox' => $user->mrd_user_mealbox,
                'mrd_user_credit' => $user->mrd_user_credit,
                'delivery_instruction' => $user->mrd_user_delivery_instruction, // Assuming this maps correctly to 'delivery_message'
                'meal_size' => $user->mrd_user_meal_size,
            ],
        ]);
    }


    //MARK: USER UPDATE
    public function userUpdate(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'delivery_instruction' => 'nullable|string|max:500',
        ]);

        // Extract the token from the Authorization header
        $token = $request->bearerToken();

        // Check if the token is provided
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token is missing'
            ], 400);
        }

        // Find the user by the session token
        $user = User::where('mrd_user_session_token', $token)->first();

        // Check if the user is found
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found'
            ], 404);
        }

        // Update the user's name and address
        $user->mrd_user_first_name = $request->input('name');
        $user->mrd_user_address = $request->input('address');
        $user->mrd_user_delivery_instruction = $request->input('delivery_instruction');
        $user->save();

        // Return a JSON response with the updated user information
        return response()->json([
            'status' => 'success',
            'message' => 'User information updated successfully',
            'data' => $user
        ]);
    }
}
