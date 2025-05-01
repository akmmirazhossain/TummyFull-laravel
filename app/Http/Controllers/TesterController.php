<?php

// app/Http/Controllers/TesterController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\OrderController;
use App\Services\MealboxService;

class TesterController extends Controller
{

    public function testyDalbhath()
    {
        $mealboxService = new MealboxService();
        // $userId = 1; // Replace with a valid user ID in your DB
        // $quantity = 3;

        //
        // $result = $mealboxService->mealboxExtra($userId, $quantity);

        // return response()->json([
        //     'success' => true,
        //     'result' => $result,
        // ]);

        // $mboxExtraQty = 2; // or whatever number you want to test
        // $result = $mealboxService->mealboxExtraPrice($mboxExtraQty);

        // return response()->json([
        //     'requested_qty' => $mboxExtraQty,
        //     'extra_price' => $result,
        // ]);


    }
}
