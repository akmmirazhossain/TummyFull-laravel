<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{



    // public function rechargeWallet(Request $request)
    // {
    //     // Validate the request data
    //     $validated = $request->validate([
    //         'phone' => 'required|string|max:11',
    //         'amount' => 'required|numeric|min:1',
    //     ]);

    //     // Retrieve phone and amount from the request
    //     $phone = $validated['phone'];
    //     $amount = $validated['amount'];


    //     $user = DB::table('mrd_user')->where('mrd_user_phone', $phone)->first();


    //     if ($user) {
    //         // ADD NEW CREDIT
    //         DB::table('mrd_user')
    //             ->where('mrd_user_phone', $phone)
    //             ->increment('mrd_user_credit', $amount);


    //         $userId = DB::table('mrd_user')
    //             ->where('mrd_user_phone', $phone)
    //             ->value('mrd_user_id');


    //         $notif_message = 'You have successfully recharged your wallet with ৳' . $amount;

    //         //NOTIFICATION INSERT
    //         $notifInsert = DB::table('mrd_notification')->insert([
    //             'mrd_notif_user_id' =>
    //             $userId,
    //             'mrd_notif_message' => $notif_message,


    //             'mrd_notif_type' => 'wallet'
    //         ]);

    //         //GET OLDEST PENDING ORDER
    //         $nextOrder = DB::table('mrd_order')
    //             ->where('mrd_order_user_id', $userId)
    //             ->where('mrd_order_status', 'pending')
    //             ->orderBy('mrd_order_date', 'asc')
    //             ->first();






    //         if ($nextOrder) {

    //             $nextOrderId = $nextOrder->mrd_order_id;
    //             $nextOrderTotalPrice = $nextOrder->mrd_order_total_price;


    //             //GET UPDATED CREDIT 
    //             $userCreditUpdated = DB::table('mrd_user')
    //                 ->where(
    //                     'mrd_user_id',
    //                     $userId
    //                 )
    //                 ->value('mrd_user_credit');


    //             // 200 INITITAL CREDIT 

    //             // 2024-08-01  CALCULATED
    //             // 150 
    //             // coc = 0 

    //             // 2024-08-02
    //             // 50 - 100 = -50  (subtotal credit)
    //             // coc = 50

    //             // 2024-08-05
    //             // -50 - 150 = -200  (subtotal credit)
    //             // coc = 0



    //             $userCreditUpdated = DB::table('mrd_user')
    //                 ->where(
    //                     'mrd_user_id',
    //                     $userId
    //                 )
    //                 ->value('mrd_user_credit');

    //             if (
    //                 $userCreditUpdated >= $nextOrderTotalPrice
    //             ) {
    //                 //$userCreditUpdatedNew = $userCreditUpdated - $nextOrderTotalPrice;
    //                 $cash_to_get = 0;
    //             } else {
    //                 // $userCreditUpdatedNew = 0;
    //                 $cash_to_get = $nextOrderTotalPrice - $userCreditUpdated;
    //             }

    //             //CASH TO GET UPDATE
    //             $cashToGet = DB::table('mrd_order')
    //                 ->where('mrd_order_id', $nextOrderId)
    //                 ->update(['mrd_order_cash_to_get' => $cash_to_get]);
    //         }



    //         return response()->json(['message' => 'Wallet recharged successfully', 'phone' => $phone, 'amount' => $amount]);
    //     } else {

    //         return response()->json(['message' => 'User with phone number does not exist', 'phone' => $phone], 404);
    //     }
    // }
}
