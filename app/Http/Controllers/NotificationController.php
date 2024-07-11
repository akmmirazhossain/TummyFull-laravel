<?php

namespace App\Http\Controllers;

use App\Http\Controllers\MenuController;
use Illuminate\Http\Request;

use \App\Models\OrderMod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;


class NotificationController extends Controller
{
    //MARK: PLACE ORDER
    public function notifOrderPlace(Request $request)
    {
        $menuId = $request->input('menuId');
        $date = $request->input('date');
        $price = $request->input('price');
        $TFLoginToken = $request->input('TFLoginToken');
        $switchValue = $request->input('switchValue');
        $quantity = $request->input('quantity');
        // Fetch user ID based on session token
        // $userId = \App\Models\User::where('mrd_user_session_token', $TFLoginToken)
        //     ->value('mrd_user_id');



        // $menuController = new MenuController();
        // $menuPeriod = $menuController->getMenuPeriod($menuId);

        // $notifInsert = DB::table('mrd_notification')->insert([
        //     'mrd_notif_user_id' =>
        //     $userId,
        //     'mrd_notif_message' => 'You have ordered a ' . $menuPeriod . ' on ' . $date,
        //     'mrd_notif_type' => 'order'
        // ]);
    }

    //MARK: quantityChng
    public function quantityChanger(Request $request)
    {
    }

    //MARK: Mbox stat
    public function getUserMealboxById($id)
    {
    }

    //MARK: Mbox Stat API
    public function mealboxStatApi(Request $request)
    {
    }
}
