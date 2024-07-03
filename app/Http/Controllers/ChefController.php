<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChefController extends Controller
{
    //MARK: Chef Now
    public function orderListChefNow()
    {
        // Get today's date in YYYY-MM-DD format
        $today = Carbon::today()->format('Y-m-d');
        $today_day_name = strtolower(Carbon::parse($today)->format('D'));

        // Fetch menu and orders, including days without orders
        $menus = DB::table('mrd_menu')
            ->select('mrd_menu_day', 'mrd_menu_period', 'mrd_menu_food_id', 'mrd_menu_price')
            ->where('mrd_menu_day', $today_day_name)
            ->get();

        // Fetch food details and map them by their IDs
        $foods = DB::table('mrd_food')
            ->select('mrd_food_id', 'mrd_food_name', 'mrd_food_desc', 'mrd_food_price', 'mrd_food_img', 'mrd_food_type')
            ->get()
            ->keyBy('mrd_food_id')
            ->toArray();

        // Fetch orders
        $orders = DB::table('mrd_order')
            ->select('mrd_order.mrd_order_date', 'mrd_menu.mrd_menu_period', 'mrd_menu.mrd_menu_food_id', 'mrd_order.mrd_order_quantity', 'mrd_order.mrd_order_total_price', 'mrd_user.mrd_user_mealbox')
            ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->leftJoin('mrd_user', 'mrd_order.mrd_order_user_id', '=', 'mrd_user.mrd_user_id')
            ->where('mrd_order.mrd_order_status', '=', 'pending')
            ->where('mrd_order.mrd_order_date', '=', $today)
            ->get();

        $result = [];

        foreach ($menus as $menu) {
            $day = $today_day_name;
            $period = $menu->mrd_menu_period;
            $food_ids = explode(',', $menu->mrd_menu_food_id); // Split food_ids into an array
            $menu_price = $menu->mrd_menu_price;

            // Initialize date entry if not exists
            if (!isset($result[$today])) {
                $result[$today] = [
                    'day' => $day,
                    'lunch' => [
                        'food_id' => [],
                        'total_quantity' => 0,
                        'mrd_menu_price' => 0,
                        'mrd_order_total_price' => 0,
                        'total_mealbox' => 0  // Initialize total mealbox count
                    ],
                    'dinner' => [
                        'food_id' => [],
                        'total_quantity' => 0,
                        'mrd_menu_price' => 0,
                        'mrd_order_total_price' => 0,
                        'total_mealbox' => 0  // Initialize total mealbox count
                    ]
                ];
            }

            // Populate food details
            $food_details = [];
            foreach ($food_ids as $food_id) {
                $food_id = trim($food_id); // Trim any leading/trailing whitespace
                if (isset($foods[$food_id])) {
                    $food_details[] = [
                        'name' => $foods[$food_id]->mrd_food_name,
                        'description' => $foods[$food_id]->mrd_food_desc,
                        'price' => $foods[$food_id]->mrd_food_price,
                        'image' => $foods[$food_id]->mrd_food_img,
                        'type' => $foods[$food_id]->mrd_food_type
                    ];
                }
            }

            if ($period === 'lunch') {
                $result[$today]['lunch']['food_id'] = array_filter(array_unique($food_details, SORT_REGULAR));
                $result[$today]['lunch']['mrd_menu_price'] = $menu_price;
            } elseif ($period === 'dinner') {
                $result[$today]['dinner']['food_id'] = array_filter(array_unique($food_details, SORT_REGULAR));
                $result[$today]['dinner']['mrd_menu_price'] = $menu_price;
            }
        }

        // Aggregate orders and update the result
        foreach ($orders as $order) {
            $period = $order->mrd_menu_period;
            $food_ids = explode(',', $order->mrd_menu_food_id); // Split food_ids into an array
            $quantity = $order->mrd_order_quantity;
            $order_total_price = $order->mrd_order_total_price;
            $user_mealbox = $order->mrd_user_mealbox;

            // Aggregate quantities and total price based on menu period
            if ($period === 'lunch') {
                foreach ($food_ids as $food_id) {
                    $food_id = trim($food_id); // Trim any leading/trailing whitespace
                    if (!empty($food_id)) {
                        $food_detail = [
                            'name' => $foods[$food_id]->mrd_food_name,
                            'description' => $foods[$food_id]->mrd_food_desc,
                            'price' => $foods[$food_id]->mrd_food_price,
                            'image' => $foods[$food_id]->mrd_food_img,
                            'type' => $foods[$food_id]->mrd_food_type
                        ];
                        if (!in_array($food_detail, $result[$today]['lunch']['food_id'])) {
                            $result[$today]['lunch']['food_id'][] = $food_detail;
                        }
                    }
                }
                $result[$today]['lunch']['total_quantity'] += $quantity;
                $result[$today]['lunch']['mrd_order_total_price'] += $order_total_price;

                // Count mealboxes for lunch if user prefers
                if ($user_mealbox == 1) {
                    $result[$today]['lunch']['total_mealbox']++;
                }
            } elseif ($period === 'dinner') {
                foreach ($food_ids as $food_id) {
                    $food_id = trim($food_id); // Trim any leading/trailing whitespace
                    if (!empty($food_id)) {
                        $food_detail = [
                            'name' => $foods[$food_id]->mrd_food_name,
                            'description' => $foods[$food_id]->mrd_food_desc,
                            'price' => $foods[$food_id]->mrd_food_price,
                            'image' => $foods[$food_id]->mrd_food_img,
                            'type' => $foods[$food_id]->mrd_food_type
                        ];
                        if (!in_array($food_detail, $result[$today]['dinner']['food_id'])) {
                            $result[$today]['dinner']['food_id'][] = $food_detail;
                        }
                    }
                }
                $result[$today]['dinner']['total_quantity'] += $quantity;
                $result[$today]['dinner']['mrd_order_total_price'] += $order_total_price;

                // Count mealboxes for dinner if user prefers
                if ($user_mealbox == 1) {
                    $result[$today]['dinner']['total_mealbox']++;
                }
            }
        }

        // Return JSON response
        return response()->json($result);
    }

    //MARK: ChefLater
    public function orderListChefLater()
    {

        // Get orders with corresponding menu details
        $orders = DB::table('mrd_order')
            ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->selectRaw('
        mrd_order_date,
        mrd_menu_period,
        GROUP_CONCAT(DISTINCT mrd_menu_food_id) as food_ids,
        SUM(mrd_order_quantity) as total_quantity,
        MAX(mrd_menu.mrd_menu_price) as menu_price,
        CAST(SUM(mrd_order_total_price) AS UNSIGNED) as total_price
    ')
            ->where('mrd_order_date', '>=', Carbon::now()->addDay()->toDateString())
            ->where('mrd_order_status', 'pending')
            ->groupBy('mrd_order_date', 'mrd_menu_period')
            ->orderBy('mrd_order_date', 'ASC')
            ->get();

        // Transform orders into a nested structure by date and period
        $transformedOrders = [];
        foreach ($orders as $order) {
            $date = $order->mrd_order_date;
            $period = $order->mrd_menu_period;

            if (!isset($transformedOrders[$date])) {
                $transformedOrders[$date] = [];
            }

            // Convert food IDs to food names
            $foodIds = array_unique(array_filter(explode(',', $order->food_ids)));
            $foodNames = DB::table('mrd_food')
                ->whereIn('mrd_food_id', $foodIds)
                ->pluck('mrd_food_name')
                ->toArray();

            $transformedOrders[$date][$period] = [
                'food_names' => $foodNames,
                'total_quantity' => $order->total_quantity ?? 0,
                'menu_price' => $order->menu_price ?? 0,
                'total_price' => $order->total_price ?? 0,
            ];
        }

        // Get all menu details to ensure all periods (lunch and dinner) are shown for each day
        $menus = DB::table('mrd_menu')
            ->select('mrd_menu_day', 'mrd_menu_food_id', 'mrd_menu_period', 'mrd_menu_price')
            ->get();

        // Create a map for menus by day and period
        $menuMap = [];
        foreach ($menus as $menu) {
            $day = $menu->mrd_menu_day;
            $period = $menu->mrd_menu_period;

            if (!isset($menuMap[$day])) {
                $menuMap[$day] = [];
            }

            // Convert menu food IDs to food names
            $menuFoodIds = array_unique(array_filter(explode(',', $menu->mrd_menu_food_id)));
            $menuFoodNames = DB::table('mrd_food')
                ->whereIn('mrd_food_id', $menuFoodIds)
                ->pluck('mrd_food_name')
                ->toArray();

            $menuMap[$day][$period] = [
                'food_names' => $menuFoodNames,
                'menu_price' => $menu->mrd_menu_price,
            ];
        }

        // Ensure each date has lunch and dinner periods from the menu map
        foreach ($menuMap as $day => $periods) {
            $date = now()->next($day)->toDateString(); // Find the next date for the given day

            if (!isset($transformedOrders[$date])) {
                $transformedOrders[$date] = [];
            }

            foreach (['lunch', 'dinner'] as $period) {
                if (!isset($transformedOrders[$date][$period])) {
                    $transformedOrders[$date][$period] = [
                        'food_names' => $periods[$period]['food_names'] ?? [],
                        'total_quantity' => 0,
                        'menu_price' => $periods[$period]['menu_price'] ?? 0,
                        'total_price' => 0,
                    ];
                } else {
                    // Update food_names and menu_price with default if missing
                    $transformedOrders[$date][$period]['food_names'] = array_unique(array_merge($periods[$period]['food_names'] ?? [], $transformedOrders[$date][$period]['food_names']));
                    $transformedOrders[$date][$period]['menu_price'] = $transformedOrders[$date][$period]['menu_price'] ?: ($periods[$period]['menu_price'] ?? 0);
                }
            }
        }

        // Reorder the array to ensure 'lunch' appears before 'dinner'
        foreach ($transformedOrders as $date => &$periods) {
            if (isset($periods['lunch']) && isset($periods['dinner'])) {
                $periods = ['lunch' => $periods['lunch'], 'dinner' => $periods['dinner']];
            } elseif (isset($periods['lunch'])) {
                $periods = ['lunch' => $periods['lunch']];
            } elseif (isset($periods['dinner'])) {
                $periods = ['dinner' => $periods['dinner']];
            }
        }

        return response()->json($transformedOrders);
    }
}
