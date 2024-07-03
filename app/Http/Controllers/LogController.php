<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;



class LogController extends Controller
{


    //MARK: quantityChng
    public function mealBook(Request $request)
    {
        // Fetch orders along with food names, menu prices, promotional prices, and discount
        // Assuming $userId is passed through the request
        $userId = '1';

        // Fetch orders along with food names, menu prices, promotional prices, and discount for the specific user
        $orders = DB::table('mrd_order')
            ->select(
                'mrd_order_date',
                'mrd_menu_period',
                'mrd_order_total_price',
                'mrd_order_quantity',
                'mrd_order_status',
                'mrd_order_discount', // New column added
                'mrd_food.mrd_food_name',
                'mrd_menu.mrd_menu_price',
                'mrd_menu.mrd_menu_price_promo'
            )
            ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->join('mrd_food', function ($join) {
                $join->on(DB::raw("FIND_IN_SET(mrd_food.mrd_food_id, REPLACE(mrd_menu.mrd_menu_food_id, ',', ','))"), '>', DB::raw("'0'"));
            })
            ->where('mrd_order.mrd_order_user_id', $userId) // Filter by user ID
            ->orderByRaw('`mrd_order`.`mrd_order_date` DESC') // Sort by date in descending order
            ->get();

        $periodOrder = ['lunch', 'dinner']; // Define the desired order


        $formattedOrders = [];

        foreach ($orders as $order) {
            $date = date('jS M', strtotime($order->mrd_order_date));
            $period = $order->mrd_menu_period;
            $totalPrice = $order->mrd_order_total_price;
            $quantity = $order->mrd_order_quantity;
            $status = $order->mrd_order_status;
            $foodName = $order->mrd_food_name;
            $menuPrice = $order->mrd_menu_price;
            $menuPricePromo = $order->mrd_menu_price_promo;
            $discount = $order->mrd_order_discount;

            if (!isset($formattedOrders[$date][$period])) {
                $formattedOrders[$date][$period] = [
                    'food_names' => [$foodName],
                    'total_price' => $totalPrice,
                    'quantity' => $quantity,
                    'status' => $status,
                    'menu_price' => $menuPrice,
                    'menu_price_promo' => $menuPricePromo,
                    'discount' => $discount // Include the discount here
                ];
            } else {
                $formattedOrders[$date][$period]['total_price'] = $totalPrice;
                $formattedOrders[$date][$period]['food_names'][] = $foodName;
                $formattedOrders[$date][$period]['quantity'] = $quantity;
                $formattedOrders[$date][$period]['status'] = $status;
                $formattedOrders[$date][$period]['menu_price'] = $menuPrice;
                $formattedOrders[$date][$period]['menu_price_promo'] = $menuPricePromo;
                $formattedOrders[$date][$period]['discount'] = $discount; // Include the discount here
            }
        }

        // Sort each date's periods
        foreach ($formattedOrders as $date => &$periods) {
            uksort($periods, function ($a, $b) use ($periodOrder) {
                return array_search($a, $periodOrder) - array_search($b, $periodOrder);
            });
        }


        return response()->json($formattedOrders);
    }
}
