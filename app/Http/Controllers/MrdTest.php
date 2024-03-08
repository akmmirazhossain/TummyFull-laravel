<?php

// app/Http/Controllers/MealController.php

//EXPLAINING THE DAY TRANSITION
// if (current_DAY)
// {
    // 1. LUNCH 
    // if (current_time >  mrd_setting_time_limit_lunch.9:00 am)
    // {
    //  * Disable the "Proceed to order" button for lunch
    //  * Keep the menus of that day visible
    // }

    // 2. DINNER 
    // if (current_time >  mrd_setting_time_limit_dinner.5:00 pm)
    // {
    //  * Show the menu of the next day
    //
    // }
// }
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class MrdTest extends Controller
{
    public function index()
    {
   
    $time1 = strtotime("9:00 pm");
$time2 = strtotime("9:01 pm");

echo $time1.'<br>';
echo $time2.'<br>';

if ($time1 > $time2) {
    echo "9:00 am is later than 9:00 pm";
} else {
    echo "9:00 pm is later than 9:00 am";
}

    }
    
}
