<?php
// app/Http/Controllers/ApiController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function getItems()
    {
        $items = [
            ['id' => 1, 'period' => 'Lunch'],
           
        ];

        return response()->json($items);
    }
}
