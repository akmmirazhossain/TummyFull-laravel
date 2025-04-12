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


    public function show($id)
    {
        $user = DB::table('mrd_user')->where('mrd_user_id', $id)->first();
        $orderCount = DB::table('mrd_order')->where('mrd_order_user_id', $id)->count();

        return view('user_show', compact('user', 'orderCount'));
    }
}
