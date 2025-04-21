<?php

// app/Http/Controllers/TesterController.php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\OrderController;

class TesterController extends Controller
{

    public function testyDalbhath()
    {
        $request = new \Illuminate\Http\Request();
        $request->replace([
            'menuId' => 3,
            'date' => '2025-04-22',
            'TFLoginToken' => '324sdf1182acbf84cc9d0ea3e69feb97a77f66223f43652477e4c33128',
            'switchValue' => 1,
            'quantity' => 2,
            'orderType' => 'regular',
            'selectedFoods' => [1, 2]
        ]);

        $orderController = new \App\Http\Controllers\OrderController();
        return $orderController->orderPlace($request);
    }
}
