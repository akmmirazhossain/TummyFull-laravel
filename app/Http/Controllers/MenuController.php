<?php

namespace App\Http\Controllers;

use App\Models\FoodMod;
use App\Models\MenuMod;
use App\Models\SettingMod;
use Illuminate\Http\Request;

use App\Models\OrderMod;


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
        $TFLoginToken = $request->query('TFLoginToken');
        $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)->value('mrd_user_id');


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
                $nextMenuText = "Tomorrow's menu (on currentday)";
                $tomorrowTaken = 1;
                $date = date('Y-m-d', strtotime(" +1 day"));
            } elseif (($day === $nextDay) && ($tomorrowTaken != 1)) {
                $nextMenuText = "Tomorrow's menu (tomorrow)";
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

            // Get all menu items for the current day
            $menus = MenuMod::where('mrd_menu_day', $day)->get();



            foreach ($menus as $menu) {
                // Get the list of food IDs from mrd_menu_food_id and remove trailing commas
                $foodIds = array_filter(explode(',', $menu->mrd_menu_food_id));

                $foods = FoodMod::whereIn('mrd_food_id', $foodIds)->get();

                $foodDataList = $foods->map(function ($food) {
                    return [
                        'food_name' => $food->mrd_food_name,
                        'food_image' => $food->mrd_food_img
                    ];
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
                            $status = $this->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                            $foodData['status'] = $status;
                            // Get the quantity for lunch and add it to food data
                            $quantity = $this->getQuantity($userId, $menu->mrd_menu_id, $date);
                            $foodData['quantity'] = $quantity;
                            $dayData['lunch'] = $foodData;
                        }


                        if (($currentTimeUnix > $limitLunchUnix) && ($currentTimeUnix > $limitDinnerUnix)) {

                            // Check if the order exists for lunch
                            $foodData['id'] = $menu->mrd_menu_id;
                            $status = $this->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                            $foodData['status'] = $status;
                            // Get the quantity for lunch and add it to food data
                            $quantity = $this->getQuantity($userId, $menu->mrd_menu_id, $date);
                            $foodData['quantity'] = $quantity;
                            $dayData['lunch'] = $foodData;
                        }
                    } else {

                        // Check if the order exists for lunch
                        $foodData['id'] = $menu->mrd_menu_id;
                        $status = $this->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                        $foodData['status'] = $status;
                        // Get the quantity for lunch and add it to food data
                        $quantity = $this->getQuantity($userId, $menu->mrd_menu_id, $date);
                        $foodData['quantity'] = $quantity;
                        $dayData['lunch'] = $foodData;
                    }
                } elseif ($menu->mrd_menu_period === 'dinner') {
                    $foodData['id'] = $menu->mrd_menu_id;


                    // Check if the order exists for dinner
                    $status = $this->getOrderStatus($userId, $menu->mrd_menu_id, $date, 'pending');
                    $foodData['status'] = $status;
                    // Get the quantity for dinner and add it to food data
                    $quantity = $this->getQuantity($userId, $menu->mrd_menu_id, $date);
                    $foodData['quantity'] = $quantity;

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


    public  function getOrderStatus($userId, $menuId, $date, $status)
    {
        // Check if the order exists
        $orderExistance = OrderMod::where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)
            ->where('mrd_order_status', $status)
            ->exists();

        return $orderExistance ? 'enabled' : 'disabled';
    }

    private function getQuantity($userId, $menuId, $date)
    {
        // Retrieve the order and get the quantity
        $order = OrderMod::where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)
            ->first();

        // Return the quantity if order exists, otherwise return 0
        return $order ? $order->mrd_order_quantity : 0;
    }

    public function getMenuById($menuId)
    {
        $meals = MenuMod::with('food')->get();

        $result = [];

        foreach ($meals as $meal) {
            if ($meal->mrd_menu_id == $menuId) {
                $foodIds = explode(',', $meal->mrd_menu_food_id);

                foreach ($foodIds as $foodId) {
                    $food = FoodMod::where('mrd_food_id', $foodId)->first();

                    if ($food) {
                        $mealData = [
                            'food_name' => $food->mrd_food_name,
                            'food_image' => $food->mrd_food_img,
                        ];

                        if ($meal->mrd_menu_period === 'lunch') {
                            $result['lunch'][] = $mealData;
                            $result['meal_type'] = $meal->mrd_menu_period;
                            $result['menu_id_lunch'] = $meal->mrd_menu_id;
                            $result['menu_price_lunch'] = $meal->mrd_menu_price;
                            $result['menu_active_lunch'] = 'yes';
                            $result['menu_day_lunch'] = $meal->mrd_menu_day;
                        } elseif ($meal->mrd_menu_period === 'dinner') {
                            $result['dinner'][] = $mealData;
                            $result['meal_type'] = $meal->mrd_menu_period;
                            $result['menu_id_dinner'] = $meal->mrd_menu_id;
                            $result['menu_price_dinner'] = $meal->mrd_menu_price;
                            $result['menu_active_dinner'] = 'yes';
                            $result['menu_day_dinner'] = $meal->mrd_menu_day;
                        }
                    }
                }

                break; // Stop the loop once the menu with the provided ID is found
            }
        }


        return response()->json($result);
    }
}
