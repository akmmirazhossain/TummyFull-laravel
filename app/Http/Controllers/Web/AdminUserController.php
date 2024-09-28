<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{

    public function user_list(Request $request)
    {
        $users = DB::select("
        SELECT 
            u.*,
            COUNT(o.mrd_order_id) as order_count
        FROM 
            mrd_user u
        LEFT JOIN 
            mrd_order o ON u.mrd_user_id = o.mrd_order_user_id
        GROUP BY 
            u.mrd_user_id
        ORDER BY 
            u.mrd_user_id DESC
    ");

        return view('user_list', compact('users'));
    }
}
