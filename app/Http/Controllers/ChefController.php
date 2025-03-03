<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChefController extends Controller
{
    //MARK: Chef Now
    public function orderListChefNow()
    {




        function getPeriod($customMeal = null)
        {
            // If user input is provided and not empty, return it
            if (!empty($customMeal)) {
                return $customMeal;
            }

            // Get current hour
            $currentHour = now()->hour;

            // Determine meal type based on time
            return $currentHour < 14 ? 'lunch' : 'dinner';
        }

        // Output: "lunch"
        $filterPeriod = getPeriod(); // Output: "dinner"

        $filterDate = request()->query('date', 'today');

        // $date = $filterDate === 'today' ? today() : ($filterDate === 'tomorrow' ? today()->addDay() : $filterDate);

        // $date = today()->addDay();
        $date = today();

        $orders = DB::table('mrd_order')
            ->leftJoin('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
            ->leftJoin('mrd_user', 'mrd_order.mrd_order_user_id', '=', 'mrd_user.mrd_user_id') // Join user table
            ->select(
                'mrd_order.*',
                'mrd_menu.mrd_menu_food_id',
                'mrd_menu.mrd_menu_period',
                'mrd_user.mrd_user_first_name',
                'mrd_user.mrd_user_last_name',
                'mrd_user.mrd_user_phone',
                'mrd_user.mrd_user_address'
            )
            ->whereDate('mrd_order.mrd_order_date', $date)
            ->when($filterPeriod, fn($query) => $query->where('mrd_menu.mrd_menu_period', $filterPeriod))
            ->orderByDesc('mrd_order.mrd_order_id')
            ->get();


        $orders->transform(function ($order) {
            if ($order->mrd_order_type === 'default' && $order->mrd_order_for === 'food' && !empty($order->mrd_menu_food_id)) {

                $foodIds = explode(',', $order->mrd_menu_food_id);

                $foodData = DB::table('mrd_food')
                    ->whereIn('mrd_food_id', $foodIds)
                    ->select('mrd_food_id', 'mrd_food_type')
                    ->get()
                    ->keyBy('mrd_food_id'); // Preserve order from $foodIds

                $selectedFoodIds = [];
                $foundTypes = [];

                foreach ($foodIds as $foodId) {
                    if (isset($foodData[$foodId])) {
                        $foodType = $foodData[$foodId]->mrd_food_type;

                        if (!isset($foundTypes[$foodType])) {
                            $foundTypes[$foodType] = true;
                            $selectedFoodIds[] = $foodId;
                        }
                    }
                }

                $foodIds = $selectedFoodIds;
            } elseif ($order->mrd_order_type === 'custom') {
                // Get food IDs from `mrd_order_custom`
                $foodIds = DB::table('mrd_order_custom')
                    ->where('mrd_order_cus_order_id', $order->mrd_order_id)
                    ->pluck('mrd_order_cus_item_id')
                    ->toArray();
            } else {
                $order->food_details = [];
                return $order;
            }



            // Fetch food details
            $foods = DB::table('mrd_food')
                ->whereIn('mrd_food_id', $foodIds)
                ->select('mrd_food_id', 'mrd_food_name', 'mrd_food_img', 'mrd_food_price', 'mrd_food_desc')
                ->get();

            $order->food_details = $foods;
            return $order;
        });

        $currentDateTime = now()->toDateTimeString();
        // Attach aggregated food data to response
        $response = [
            'currentDateTime' => $currentDateTime,
            'orders' => $orders,

        ];

        return response()->json($response);
    }



    // public function orderListChefNow(Request $request)
    // {
    //     // Set the variables for date criteria and order status
    //     $orderlistof = "todayafter";
    //     $orderstatus = "pending";
    //     $orderperiod = "all";
    //     $orderarea = "all";


    //     $TFLoginToken = $request->query('TFLoginToken');
    //     $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)->value('mrd_user_id');



    //     // Get the current date
    //     $today = Carbon::now()->format('Y-m-d');

    //     // Query the database based on the $orderlistof value
    //     $ordersQuery = DB::table('mrd_order')
    //         ->join('mrd_user', 'mrd_order.mrd_order_user_id', '=', 'mrd_user.mrd_user_id')
    //         ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
    //         // ->join('mrd_area', 'mrd_user.mrd_user_area', '=', 'mrd_area.mrd_area_id')
    //         // ->join('mrd_payment', 'mrd_order.mrd_order_id', '=', 'mrd_payment.mrd_payment_order_id')
    //         ->select(
    //             'mrd_order.mrd_order_id',
    //             'mrd_order.mrd_order_menu_id',
    //             'mrd_order.mrd_order_date',
    //             'mrd_order.mrd_order_quantity',
    //             'mrd_order.mrd_order_total_price',
    //             'mrd_order.mrd_order_cash_to_get',
    //             'mrd_order.mrd_order_mealbox',
    //             'mrd_order.mrd_order_status',
    //             'mrd_menu.mrd_menu_period',
    //             'mrd_menu.mrd_menu_id',
    //             'mrd_user.mrd_user_mealbox',
    //             'mrd_user.mrd_user_has_mealbox',
    //             'mrd_user.mrd_user_mealbox_paid',
    //             // 'mrd_user.mrd_user_first_name',
    //             // 'mrd_user.mrd_user_credit',
    //             // 'mrd_user.mrd_user_delivery_instruction',
    //             // 'mrd_area.mrd_area_name',
    //             // 'mrd_payment.mrd_payment_amount'

    //         )
    //         ->orderBy('mrd_order.mrd_order_date', 'asc');


    //     if ($userId != "") {
    //         $ordersQuery->where('mrd_user.mrd_user_chef_id', '=', $userId);
    //     }

    //     if ($orderarea !== "all") {
    //         $ordersQuery->where('mrd_area.mrd_area_id', '=', $orderarea);
    //     }

    //     if ($orderperiod !== "all") {
    //         $ordersQuery->where('mrd_menu.mrd_menu_period', '=', $orderperiod);
    //     }

    //     if ($orderlistof === 'today') {
    //         $ordersQuery->whereDate('mrd_order.mrd_order_date', '=', $today);
    //     } elseif ($orderlistof === 'todayafter') {
    //         $ordersQuery->whereDate('mrd_order.mrd_order_date', '>=', $today);
    //     }

    //     // Modify query based on the value of $orderstatus
    //     if ($orderstatus !== "all") {
    //         $ordersQuery->where('mrd_order.mrd_order_status', '=', $orderstatus);
    //     }

    //     // Fetch the orders
    //     $orders = $ordersQuery->get();



    //     function getOrderTotals($order)
    //     {
    //         $totals = DB::table('mrd_order')
    //             ->where('mrd_order_date', $order->mrd_order_date)
    //             ->where('mrd_order_menu_id', $order->mrd_order_menu_id)
    //             ->where(
    //                 'mrd_order_status',
    //                 'pending'
    //             )
    //             ->selectRaw('SUM(mrd_order_quantity) as total_quantity, SUM(mrd_order_mealbox) as total_mealbox')
    //             ->first();

    //         return [
    //             'total_quantity' => (int) $totals->total_quantity,
    //             'total_mealbox' => (int) $totals->total_mealbox,
    //         ];
    //     }





    //     function getFoodDetailsByMenu($menuId)
    //     {
    //         // Get the menu details
    //         $menu = DB::table('mrd_menu')
    //             ->where('mrd_menu_id', $menuId)
    //             ->first();

    //         if ($menu) {
    //             // Explode the food IDs into an array
    //             $foodIds = explode(',', $menu->mrd_menu_food_id);

    //             // Fetch the food details
    //             $foodDetails = DB::table('mrd_food')
    //                 ->whereIn('mrd_food_id', $foodIds)
    //                 ->get();

    //             return $foodDetails;
    //         } else {
    //             return null;
    //         }
    //     }
    //     // dd(getFoodDetailsByMenu(14));

    //     $groupedOrders = [];

    //     foreach ($orders as $order) {
    //         $date = $order->mrd_order_date;
    //         $period = $order->mrd_menu_period;



    //         $totals = getOrderTotals($order);
    //         $groupedOrders[$date][$period]['total_quantity'] = $totals['total_quantity'];
    //         $groupedOrders[$date][$period]['total_mealbox'] = $totals['total_mealbox'];
    //         // $groupedOrders[$date][$period]['total_quantity'] = getOrderTotals($order);
    //         $groupedOrders[$date][$period]['menu_id'] = $order->mrd_order_menu_id;

    //         $foodItems = getFoodDetailsByMenu($order->mrd_order_menu_id);
    //         $groupedOrders[$date][$period]['food_items'] = $foodItems;
    //     }


    //     return response()->json($groupedOrders);
    // }

    public function chefOrderHistory(Request $request)
    {

        $TFLoginToken = $request->query('TFLoginToken');
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)->value('mrd_user_id');



        $orders = DB::select(
            'SELECT ord.*, 
            s.mrd_setting_commission_chef,
            (ord.mrd_order_quantity * s.mrd_setting_commission_chef) AS total_commission 
     FROM mrd_order ord
     JOIN mrd_user user ON ord.mrd_order_user_id = user.mrd_user_id
     JOIN mrd_setting s ON 1 = 1  -- Assuming there is only one setting, adjust as needed
     WHERE user.mrd_user_chef_id = ? 
     AND ord.mrd_order_status = "delivered"
     ORDER BY ord.mrd_order_date DESC',
            [$userId]
        );



        // Return the result as JSON
        return response()->json($orders);
    }


    public function chefPaymentHistory(Request $request)
    {

        $TFLoginToken = $request->query('TFLoginToken');
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)->value('mrd_user_id');



        $payments = DB::select(
            'SELECT * 
     FROM mrd_payment 
     WHERE mrd_payment_user_id = ? 
     ORDER BY mrd_payment_id ASC',
            [$userId]
        );



        // Return the result as JSON
        return response()->json($payments);
    }
}
