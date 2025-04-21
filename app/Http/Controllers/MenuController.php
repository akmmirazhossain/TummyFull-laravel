<?php

namespace App\Http\Controllers;

use App\Models\FoodMod;
use App\Models\MenuMod;
use App\Models\SettingMod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrderMod;

//IMPORT SERVICES

use App\Services\CreditService;
use App\Services\MealboxService;
use App\Services\OrderService;

use App\Services\ResponseService;


class MenuController extends Controller
{

    public function __construct()
    {
        // Disable CORS middleware for all methods in this controller
        $this->middleware(function ($request, $next) {
            $response = $next($request);
            $response->header('Access-Control-Allow-Origin', '*');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With');
            return $response;
        });
    }

    public function index(Request $request)
    {

        $orderService = new OrderService();
        $mealboxService = new MealboxService();


        $TFLoginToken = $request->query('TFLoginToken');
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');


        //MARK: Date calc
        $currentDay = strtolower(date('D'));
        $currentTimeUnix = time();
        $currentDate = date('Y-m-d');
        $dayStartUnix = strtotime($currentDate . ' ' . '00:00');
        $nextDayUnix = $dayStartUnix + (24 * 60 * 60);

        $mrdSetting = SettingMod::first();
        $limitLunch = $mrdSetting->mrd_setting_time_limit_lunch;
        $limitDinner = $mrdSetting->mrd_setting_time_limit_dinner;

        $limitDinnerUnix = strtotime($currentDate . ' ' . $limitDinner);
        $limitLunchUnix = strtotime($currentDate . ' ' . $limitLunch);

        if ($currentTimeUnix > $limitDinnerUnix) {
            $currentDay = $this->getNextDay($currentDay);
        }

        $nextDay = $this->getNextDay($currentDay);
        $daysOfWeek = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $currentDayIndex = array_search($currentDay, $daysOfWeek);

        $startIndex = $currentDayIndex;
        $sortedDays = array_merge(
            array_slice($daysOfWeek, $startIndex),
            array_slice($daysOfWeek, 0, $startIndex)
        );

        $result = [];
        $currentIndex = 0;
        $tomorrowTaken = 0;
        $tomorrow2Taken = 0;




        $result = [];

        foreach ($sortedDays as $day) {




            $nextMenuText = '';
            $dayData = [];



            if (($day === $currentDay) && ($currentTimeUnix < $limitDinnerUnix)) {
                $nextMenuText = "Today's menu";
                $date = date('Y-m-d', strtotime("+$currentIndex days"));
            } elseif (($day === $currentDay) && ($currentTimeUnix < $nextDayUnix)) {
                $nextMenuText = "Tomorrow's menu ";
                $tomorrowTaken = 1;
                $date = date('Y-m-d', strtotime(" +1 day"));
            } elseif (($day === $nextDay) && ($tomorrowTaken != 1)) {
                $nextMenuText = "Tomorrow's menu ";
                $tomorrow2Taken = 1;
                $date = date('Y-m-d', strtotime(" +1 day"));
            } else {
                $nextMenuText = "Menu of";
                if ($tomorrowTaken == 1) {
                    $date = date('Y-m-d', strtotime("tomorrow + $currentIndex days"));
                } elseif ($tomorrow2Taken == 1) {
                    $date = date('Y-m-d', strtotime(" +$currentIndex days"));
                }
            }

            $dayData['date'] = $date;
            $dayData['menu_of'] = $nextMenuText;

            $currentIndex++;

            //MENU ITEMS CURRENT DAY
            $menus = MenuMod::where('mrd_menu_day', $day)->get();



            foreach ($menus as $menu) {


                // GET FOOD INFO FROM ID (menu table)
                $foodIds = array_filter(explode(',', $menu->mrd_menu_food_id));

                // Fetch custom food IDs if conditions are met
                $status = $orderService->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                $orderType = $orderService->orderType($userId, $menu->mrd_menu_id, $date, 'pending');


                $customFoodIds = [];
                if ($status === 'enabled' && $orderType === 'custom') {

                    $orderId = $orderService->getOrderId($userId, $menu->mrd_menu_id, $date);


                    $customFoodIds = DB::table('mrd_order_custom')
                        ->where('mrd_order_cus_order_id', $orderId)
                        ->pluck('mrd_order_cus_item_id')
                        ->toArray();
                }

                // dd($customFoodIds);



                $foods = FoodMod::whereIn('mrd_food_id', $foodIds)->get();

                // Sort the collection based on the updated order
                $sortedFoods = $foods->sortBy(function ($food) use ($foodIds, $customFoodIds) {
                    $index = array_search($food->mrd_food_id, $customFoodIds);
                    return $index !== false ? $index - count($foodIds) : array_search($food->mrd_food_id, $foodIds);
                });


                // Group the sorted foods by type
                $foodDataList = $sortedFoods->groupBy('mrd_food_type')->map(function ($group) {
                    return $group->map(function ($food) {
                        return [
                            'food_id' => $food->mrd_food_id,
                            'food_name' => $food->mrd_food_name,
                            'food_image' => $food->mrd_food_img
                        ];
                    })->values(); // Reset array keys for JSON output
                });







                $foodData = [
                    'foods' => $foodDataList,
                    'price' => $menu->mrd_menu_price,
                ];

                // Assign appropriate ID based on the meal period
                if ($menu->mrd_menu_period === 'lunch') {

                    if ($day == $currentDay) {


                        if ($currentTimeUnix < $limitLunchUnix) {

                            // Check if the order exists for lunch
                            $foodData['id'] = $menu->mrd_menu_id;
                            $status = $orderService->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                            $foodData['status'] = $status;

                            $orderType = $orderService->orderType($userId, $menu->mrd_menu_id, $date, 'pending');
                            $foodData['order_type'] = $orderType;
                            // if ($status ==='enabled' && $orderType ==='custom' )
                            // {}
                            // Get the quantity for lunch and add it to food data
                            $quantity = $orderService->getQuantity($userId, $menu->mrd_menu_id, $date);
                            $foodData['quantity'] = $quantity;

                            $mealboxStatus = $mealboxService->getMealboxStatus($userId, $menu->mrd_menu_id, $date);
                            $foodData['mealbox'] = $mealboxStatus;
                            $dayData['lunch'] = $foodData;
                        }


                        if (($currentTimeUnix > $limitLunchUnix) && ($currentTimeUnix > $limitDinnerUnix)) {

                            // Check if the order exists for lunch
                            $foodData['id'] = $menu->mrd_menu_id;
                            $status = $orderService->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                            $foodData['status'] = $status;

                            $orderType = $orderService->orderType($userId, $menu->mrd_menu_id, $date, 'pending');
                            $foodData['order_type'] = $orderType;
                            // Get the quantity for lunch and add it to food data
                            $quantity = $orderService->getQuantity($userId, $menu->mrd_menu_id, $date);
                            $foodData['quantity'] = $quantity;
                            $mealboxStatus = $mealboxService->getMealboxStatus($userId, $menu->mrd_menu_id, $date);
                            $foodData['mealbox'] = $mealboxStatus;
                            $dayData['lunch'] = $foodData;
                        }
                    } else {

                        // Check if the order exists for lunch
                        $foodData['id'] = $menu->mrd_menu_id;
                        $status = $orderService->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                        $foodData['status'] = $status;

                        $orderType = $orderService->orderType(
                            $userId,
                            $menu->mrd_menu_id,
                            $date,
                            'pending'
                        );
                        $foodData['order_type'] = $orderType;
                        // Get the quantity for lunch and add it to food data
                        $quantity = $orderService->getQuantity($userId, $menu->mrd_menu_id, $date);
                        $foodData['quantity'] = $quantity;
                        $mealboxStatus = $mealboxService->getMealboxStatus($userId, $menu->mrd_menu_id, $date);
                        $foodData['mealbox'] = $mealboxStatus;
                        $dayData['lunch'] = $foodData;
                    }
                } elseif ($menu->mrd_menu_period === 'dinner') {
                    $foodData['id'] = $menu->mrd_menu_id;


                    // Check if the order exists for dinner
                    $status = $orderService->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                    $foodData['status'] = $status;

                    $orderType = $orderService->orderType($userId, $menu->mrd_menu_id, $date, 'pending');
                    $foodData['order_type'] = $orderType;
                    // Get the quantity for dinner and add it to food data
                    $quantity = $orderService->getQuantity($userId, $menu->mrd_menu_id, $date);
                    $foodData['quantity'] = $quantity;
                    $mealboxStatus = $mealboxService->getMealboxStatus($userId, $menu->mrd_menu_id, $date);
                    $foodData['mealbox'] = $mealboxStatus;
                    $dayData['dinner'] = $foodData;
                }
            }

            $result[$day] = $dayData;
        }

        // Print the result
        return response()->json($result);
    }

    private function getNextDay($currentDay)
    {
        $daysOfWeek = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $currentIndex = array_search($currentDay, $daysOfWeek);

        $nextIndex = ($currentIndex + 1) % 7;

        return $daysOfWeek[$nextIndex];
    }
}
