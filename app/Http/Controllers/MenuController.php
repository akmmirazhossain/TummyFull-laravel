<?php

namespace App\Http\Controllers;

use App\Models\FoodMod;
use App\Models\MenuMod;
use App\Models\SettingMod;

class MenuController extends Controller
{
    public function index()
    {
        $currentDay = strtolower(date('D'));
        //$currentTime = date('H:i a');
        $currentTimeUnix = time();
        $currentDate = date('Y-m-d');
        $dayStartUnix = strtotime($currentDate . ' ' . '00:00');
        $nextDayUnix = $dayStartUnix + (24 * 60 * 60);

        //dd($currentDate.' '. $currentTime);

        $mrdSetting = SettingMod::first();

        $limitLunch = $mrdSetting->mrd_setting_time_limit_lunch;
        $limitDinner = $mrdSetting->mrd_setting_time_limit_dinner;

//dd($limitLunch);

        $limitDinnerUnix = $currentDate . ' ' . $limitDinner;
        $limitDinnerUnix = strtotime($limitDinnerUnix);

        $limitLunchUnix = $currentDate . ' ' . $limitLunch;
        $limitLunchUnix = strtotime($limitLunchUnix);

        // Check if the current time is after the time_limit_dinner
        if ($currentTimeUnix > $limitDinnerUnix) {
            // If it is, get the next day
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

        $meals = MenuMod::with('food')->get();

        $result = [];

        foreach ($meals as $meal) {
            $foodIds = explode(',', $meal->mrd_menu_food_id);
            $menuId = $meal->mrd_menu_id;

            $addedMenuInfoForLunch = false;
            $addedMenuInfoForDinner = false;

            foreach ($foodIds as $foodId) {
                $food = FoodMod::where('mrd_food_id', $foodId)->first();

                if ($food) {
                    $mealData = [
                        'food_name' => $food->mrd_food_name,
                        'food_image' => $food->mrd_food_img,
                    ];

                    if ($meal->mrd_menu_period === 'lunch') {
                        $result[$meal->mrd_menu_day][$meal->mrd_menu_period][] = $mealData;
                    } elseif ($meal->mrd_menu_period === 'dinner') {
                        $result[$meal->mrd_menu_day][$meal->mrd_menu_period][] = $mealData;
                    }

                    if (!$addedMenuInfoForLunch && $meal->mrd_menu_period === 'lunch') {
                        $result[$meal->mrd_menu_day]['menu_id_lunch'] = $menuId;
                        $result[$meal->mrd_menu_day]['menu_price_lunch'] = $meal->mrd_menu_price;
                        if (($currentTimeUnix > $limitLunchUnix) && ($currentTimeUnix < $limitDinnerUnix)) {
                            $result[$meal->mrd_menu_day]['menu_active_lunch'] = 'no';
                        } else {
                            $result[$meal->mrd_menu_day]['menu_active_lunch'] = 'yes';
                        }

                        $addedMenuInfoForLunch = true;
                    } elseif (!$addedMenuInfoForDinner && $meal->mrd_menu_period === 'dinner') {
                        $result[$meal->mrd_menu_day]['menu_id_dinner'] = $menuId;
                        $result[$meal->mrd_menu_day]['menu_price_dinner'] = $meal->mrd_menu_price;
                        $result[$meal->mrd_menu_day]['menu_active_dinner'] = 'yes';
                        $addedMenuInfoForDinner = true;
                    }
                }
            }
        }

        $output = [];

        $currentIndex = 0;
        $tomorrowTaken = 0;

        foreach ($sortedDays as $day) {
            $date = date('jS M', strtotime("+$currentIndex days"));
            $nextMenuText = '';

            // if (($currentTimeUnix > $dayStartUnix) && ($currentTimeUnix < $limitDinnerUnix)) {

            //     $nextMenuText = "Today's menu";

            // } elseif (($currentTimeUnix > $limitDinnerUnix) && ($currentTimeUnix < $nextDayUnix)) {
            //     $nextMenuText = "Tomorrow's menu";
            // } else {
            //     $nextMenuText = "Menu of";
            // }

            if (($day === $currentDay) && ($currentTimeUnix < $limitDinnerUnix)) {

                $nextMenuText = "Today's menu";

            } elseif (($day === $currentDay) && ($currentTimeUnix < $nextDayUnix)) {
                $nextMenuText = "Tomorrow's menu 1";
                $tomorrowTaken = 1;
            } elseif (($day === $nextDay) && ($tomorrowTaken != 1)) {
                $nextMenuText = "Tomorrow's menu 2";
            } else {
                $nextMenuText = "Menu of";
            }

            $result[$day]['date'] = $date;
            $result[$day]['menu_of'] = $nextMenuText;
            //$result[$day]['menu_active_lunch'] = (($currentTime > $limitLunch) && ($day === $currentDay)) ? 'no (L2)' : 'yes (L2)';

            // Check if the current time is after the time_limit_lunch
            if (($currentTimeUnix > $limitLunchUnix) && ($currentTimeUnix < $limitDinnerUnix) && ($day === $currentDay)) {

                $result[$day]['menu_active_lunch'] = 'no';
            } else {
                // If the current time is not after the time_limit_lunch, set 'menu_active_lunch' to 'yes (L2)'
                $result[$day]['menu_active_lunch'] = 'yes';
            }

            $output[$day] = $result[$day];

            $currentIndex++; // Increment for the next day
        }

        return response()->json($output);
    }

    private function getNextDay($currentDay)
    {
        $daysOfWeek = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $currentIndex = array_search($currentDay, $daysOfWeek);

        $nextIndex = ($currentIndex + 1) % 7;

        return $daysOfWeek[$nextIndex];
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
                        $result['menu_id_lunch'] = $meal->mrd_menu_id;
                        $result['menu_price_lunch'] = $meal->mrd_menu_price;
                        $result['menu_active_lunch'] = 'yes';
                    } elseif ($meal->mrd_menu_period === 'dinner') {
                        $result['dinner'][] = $mealData;
                        $result['menu_id_dinner'] = $meal->mrd_menu_id;
                        $result['menu_price_dinner'] = $meal->mrd_menu_price;
                        $result['menu_active_dinner'] = 'yes';
                    }
                }
            }

            break; // Stop the loop once the menu with the provided ID is found
        }
    }

    return response()->json($result);
}




}
