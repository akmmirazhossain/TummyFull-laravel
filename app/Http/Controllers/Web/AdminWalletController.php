<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\SmsController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Services\CreditService;
use App\Services\MealboxService;
use App\Services\NotifService;
use App\Services\PaymentService;
use App\Services\SettingsService;
use App\Services\OrderService;

class AdminWalletController extends Controller
{

    public function walletRecharge(Request $request)
    {


        return view('wallet_recharge');
    }

    public function walletSearchUser(Request $request)
    {
        // Ensure the phone number is sanitized and validated as needed
        $prefix = $request->phone . '%'; // Appending '%' for prefix matching

        $users = DB::table('mrd_user')
            ->select('mrd_user_id', 'mrd_user_first_name', 'mrd_user_credit', 'mrd_user_phone')
            ->where('mrd_user_phone', 'LIKE', $prefix) // Use LIKE for prefix search
            ->get(); // Use get() to return multiple results

        if ($users->isNotEmpty()) {
            return response()->json(['success' => true, 'data' => $users]); // Return multiple users
        } else {
            return response()->json(['success' => false, 'message' => 'User not found']);
        }
    }


    public function walletRechargeConfirm(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'phone' => 'required|string|max:11',
            'amount' => 'required|numeric|min:1',
        ]);

        // Retrieve phone and amount from the request
        $phone = $validated['phone'];
        $amount = $validated['amount'];

        $MealboxService = new MealboxService();
        $CreditService = new CreditService();

        $user = DB::table('mrd_user')->where('mrd_user_phone', $phone)->first();


        if ($user) {
            // ADD NEW CREDIT
            DB::table('mrd_user')
                ->where('mrd_user_phone', $phone)
                ->increment('mrd_user_credit', $amount);


            $userId = DB::table('mrd_user')
                ->where('mrd_user_phone', $phone)
                ->value('mrd_user_id');


            $notif_message = 'You have successfully recharged your wallet with ৳' . $amount;

            //NOTIFICATION INSERT
            $notifInsert = DB::table('mrd_notification')->insert([
                'mrd_notif_user_id' =>
                $userId,
                'mrd_notif_message' => $notif_message,
                'mrd_notif_type' => 'wallet'
            ]);

            // INSERT PAYMENT RECORD
            PaymentService::paymentInsert(
                $userId,
                null,
                $amount,
                'wallet',
                'paid',
                'recharge',
                null,
                'mfs',
                null,
                null,
                null,
                now()
            );

            // //GET OLDEST PENDING ORDER
            // $nextOrder = DB::table('mrd_order')
            //     ->where('mrd_order_user_id', $userId)
            //     ->where('mrd_order_status', 'pending')
            //     ->orderBy('mrd_order_date', 'asc')
            //     ->first();

            // if ($nextOrder) {

            //     $nextOrderId = $nextOrder->mrd_order_id;
            //     $nextOrderTotalPrice = $nextOrder->mrd_order_total_price;


            //     //GET UPDATED CREDIT 
            //     $userCreditUpdated = DB::table('mrd_user')
            //         ->where(
            //             'mrd_user_id',
            //             $userId
            //         )
            //         ->value('mrd_user_credit');


            //     $delivComm = DB::table('mrd_order')
            //         ->where('mrd_order_id', $nextOrderId)
            //         ->value('mrd_order_deliv_commission');

            //     if (
            //         $userCreditUpdated >= ($nextOrderTotalPrice + $delivComm)
            //     ) {
            //         //$userCreditUpdatedNew = $userCreditUpdated - $nextOrderTotalPrice;
            //         $cash_to_get = 0;
            //     } else {
            //         // $userCreditUpdatedNew = 0;
            //         $cash_to_get = ($nextOrderTotalPrice + $delivComm) - $userCreditUpdated;
            //     }

            //     //CASH TO GET UPDATE
            //     $cashToGet = DB::table('mrd_order')
            //         ->where('mrd_order_id', $nextOrderId)
            //         ->update(['mrd_order_cash_to_get' => $cash_to_get]);



            //     $message = '(dalbhath.com) You have successfully recharged your wallet with ৳' . $amount . '.';

            //     //SEND SMS
            //     $smsController = new SmsController();

            //     $smsController->insertSms($userId, $phone, $message, 'recharge');
            //     $smsController->sendSms($phone,  $message);
            // }


            $nextOrder = DB::table('mrd_order')
                ->where('mrd_order_user_id', $userId)
                ->where('mrd_order_status', 'pending')
                ->orderBy('mrd_order_date', 'asc')
                ->select('mrd_order_id', 'mrd_order_user_id', 'mrd_order_quantity', 'mrd_order_total_price', 'mrd_order_deliv_commission')
                ->first();

            //UPDATE CASH TO GET IF USER HAS A NEXT ORDER
            if (
                $nextOrder
            ) {


                //COLLECT DATA
                $userCreditUpdated = CreditService::userCredit(
                    $userId
                );

                $perMealPrice = SettingsService::perMealPrice();

                $mealboxExtra = $MealboxService->mealboxExtra($nextOrder->mrd_order_user_id,  $nextOrder->mrd_order_quantity);

                $mealboxExtraPrice = $MealboxService->mealboxExtraPrice($mealboxExtra);

                $delivComm = $nextOrder->mrd_order_deliv_commission;

                $nextOrderId = $nextOrder->mrd_order_id;
                $nextOrderQuantity = $nextOrder->mrd_order_quantity;

                //NEXT ORDER TOTAL PRICE

                $nextOrderTotalPrice =  ($nextOrderQuantity * $perMealPrice) +  $mealboxExtraPrice +   $delivComm;



                if (
                    $userCreditUpdated >= $nextOrderTotalPrice
                ) {
                    //$userCreditUpdatedNew = $userCreditUpdated - $nextOrderTotalPrice;
                    $cash_to_get = 0;
                } else {
                    // $userCreditUpdatedNew = 0;
                    $cash_to_get = $nextOrderTotalPrice - $userCreditUpdated;
                }

                // $cash_to_get = $CreditService->cashToGet($userId, $quantity);



                //CASH TO GET UPDATE
                $cashToGet = DB::table('mrd_order')
                    ->where('mrd_order_id', $nextOrderId)
                    ->update([
                        'mrd_order_cash_to_get' => $cash_to_get,
                        'mrd_order_total_price' => $nextOrderTotalPrice,
                        'mrd_order_mealbox_extra' =>  $mealboxExtra
                    ]);


                //UPDATE NOTIFICATION
                NotifService::notifUpdate($userId, $nextOrderId, null, 'order', null, null, $mealboxExtra, $cash_to_get,  null);


                // $message = '(dalbhath.com) You have successfully recharged your wallet with ৳' . $amount . '.';

                // //SEND SMS
                // $smsController = new SmsController();

                // $smsController->insertSms($userId, $phone, $message, 'recharge');
                // $smsController->sendSms($phone,  $message);
            }

            return response()->json(['success' => 'Wallet recharged successfully', 'phone' => $phone, 'amount' => $amount]);
        } else {

            return response()->json(['message' => 'User with phone number does not exist', 'phone' => $phone], 404);
        }
    }

    public function walletRechargeHistory(Request $request)
    {
        $walletRecharges = DB::table('mrd_payment')
            ->join('mrd_user', 'mrd_payment.mrd_payment_user_id', '=', 'mrd_user.mrd_user_id')
            ->select(
                'mrd_user.mrd_user_first_name',
                'mrd_user.mrd_user_phone',
                'mrd_payment.mrd_payment_amount',
                'mrd_payment.mrd_payment_user_id',
                'mrd_payment.mrd_payment_method',
                'mrd_payment.mrd_payment_date_paid'
            )
            ->where('mrd_payment.mrd_payment_type', 'wallet')
            ->orderBy('mrd_payment.mrd_payment_date_paid', 'DESC')
            ->get();

        if ($walletRecharges->isEmpty()) {
            return view('wallet_recharge_history', ['error' => 'No wallet recharges found.']);
        }

        return view('wallet_recharge_history', ['walletRecharges' => $walletRecharges]);
    }
}
