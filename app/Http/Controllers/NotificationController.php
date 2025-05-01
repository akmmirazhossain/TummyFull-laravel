<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;




class NotificationController extends Controller
{

    //MARK: GET NOTIF
    public function notifGet(Request $request)
    {
        $TFLoginToken =
            $request->header('Authorization');
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

        $notifications = DB::select("SELECT mrd_notif_message,mrd_notif_date_added,mrd_notif_quantity,mrd_notif_mealbox_extra,mrd_notif_total_price,mrd_notif_seen,mrd_notif_type FROM mrd_notification WHERE mrd_notif_user_id = $userId ORDER BY mrd_notif_id DESC LIMIT 100");

        return response()->json([

            'notifications' =>   $notifications
        ]);
    }


    //MARK: NOTIF SEEN
    public function notifSeen(Request $request)
    {
        $TFLoginToken =
            $request->header('Authorization');
        $userId = DB::table('mrd_user')
            ->where('mrd_user_session_token', $TFLoginToken)
            ->value('mrd_user_id');

        $notif_seen = DB::table('mrd_notification')
            ->where('mrd_notif_user_id', $userId)
            ->where('mrd_notif_seen', 0) // Only update rows where mrd_notif_seen is 0
            ->update(['mrd_notif_seen' => 1]);

        return response()->json([

            'notif_seen' =>   'true'
        ]);
    }
}
