<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderFoodSwapController extends Controller
{
    /**
     * Swap food in an existing order.
     */
    public function foodSwap(Request $request)
    {

        $orderDate = $request->input('date');
        $mealPeriod = $request->input('mealType');
        $category = $request->input('category');
        $currentFoodId = $request->input('currentFoodId');
        $newFoodId = $request->input('newFoodId');
        $finalFoods = $request->input('finalFoods');

        $TFLoginToken = $request->input('TFLoginToken');
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)->value('mrd_user_id');

        // Get order ID
        $orderId = DB::table('mrd_order')
            ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->where('mrd_order.mrd_order_date', $orderDate)
            ->where('mrd_order.mrd_order_user_id', $userId)
            ->where('mrd_menu.mrd_menu_period', $mealPeriod)
            ->value('mrd_order.mrd_order_id'); // Fetch single order ID

        if (!$orderId) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Update the food item in the custom order table IF FOOD SWAP then ORDER
        $updated = DB::table('mrd_order_custom')
            ->where('mrd_order_cus_order_id', $orderId)
            ->where('mrd_order_cus_item_id', $currentFoodId)
            ->update(['mrd_order_cus_item_id' => $newFoodId]);

        if ($updated) {
            return response()->json(['message' => 'IF updated -> Food swapped successfully!', 'orderId' => $orderId, 'status' => 'success']);
        } else {
            // First, update the order type to "custom"
            $custom = DB::table('mrd_order')
                ->where('mrd_order_id', $orderId)
                ->update(['mrd_order_type' => 'custom']);

            if ($custom) {
            }

            // Step 1: Swap the changed food item
            $existingEntry = DB::table('mrd_order_custom')
                ->where('mrd_order_cus_order_id', $orderId)
                ->where('mrd_order_cus_item_id', $currentFoodId)
                ->exists();

            if ($existingEntry) {
                // Update existing food item
                DB::table('mrd_order_custom')
                    ->where('mrd_order_cus_order_id', $orderId)
                    ->where('mrd_order_cus_item_id', $currentFoodId)
                    ->update(['mrd_order_cus_item_id' => $newFoodId, 'mrd_order_cus_date_update' => now()]);
            } else {

                foreach ($finalFoods as $foodId) {
                    $exists = DB::table('mrd_order_custom')
                        ->where('mrd_order_cus_order_id', $orderId)
                        ->where('mrd_order_cus_item_id', $foodId)
                        ->exists();

                    if (!$exists) { // Only insert if it doesn't exist
                        DB::table('mrd_order_custom')->insert([
                            'mrd_order_cus_order_id' => $orderId,
                            'mrd_order_cus_item_id' => $foodId,
                            'mrd_order_cus_date_update' => now(),
                        ]);
                    }
                }
            }



            return response()->json(['message' => 'ELSE (not updated) Food swap successful!', 'orderId' => $orderId, 'status' => 'success', 'custom' => $custom]);
        }
    }
}
