<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;


class AdminNotifController extends Controller
{



    public function notif_list()
    {
        $notifications = DB::table('mrd_notification')
            ->leftJoin('mrd_user', 'mrd_notification.mrd_notif_user_id', '=', 'mrd_user.mrd_user_id')
            ->select(
                'mrd_notification.mrd_notif_id',
                'mrd_user.mrd_user_first_name',
                'mrd_user.mrd_user_last_name',
                'mrd_notification.mrd_notif_seen',
                'mrd_notification.mrd_notif_message',
                'mrd_notification.mrd_notif_quantity',
                'mrd_notification.mrd_notif_total_price',
                'mrd_notification.mrd_notif_date_added'
            )
            ->orderBy('mrd_notification.mrd_notif_date_added', 'desc')
            ->limit(100)
            ->get();

        return view('notif_list', compact('notifications'));
    }
}
