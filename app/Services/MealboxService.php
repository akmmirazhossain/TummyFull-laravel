<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

use App\Services\NotifService;
use App\Services\PaymentService;

class MealboxService
{


    public function mealboxGive($userId, $quantity)
    {
        $mealboxActive = $this->mealboxActive($userId);

        if ($mealboxActive) {
            return $quantity;
        } else {
            return 0;
        }
    }


    public function mealboxExtra($userId, $quantity)
    {
        $mealboxActive = $this->mealboxActive($userId);

        if ($mealboxActive) {
            $mealboxHas = $this->mealboxHas($userId);

            if ($mealboxHas < $quantity) {
                return $quantity - $mealboxHas; // Return the extra needed
            } else {
                return 0; // No extra needed
            }
        } else {
            return 0; // Not active
        }
    }


    public function mealboxExtraPrice($mboxExtraQty)
    {
        if ($mboxExtraQty > 0) {
            $mealboxPrice = DB::table('mrd_setting')->value('mrd_setting_mealbox_price');
            return $mealboxPrice * $mboxExtraQty;
        }

        return 0;
    }

    public function mealboxHas($userId)
    {

        $mealboxHas = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_has_mealbox');

        return $mealboxHas;
    }


    public function mealboxHasUpdate($userId, $orderId, $mealboxPick)
    {


        if ($this->mealboxActive($userId)) {
            // Get current mealbox count
            $currentHas = DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->value('mrd_user_has_mealbox');

            $mealboxGive = DB::table('mrd_order')
                ->where('mrd_order_id', $orderId)
                ->value('mrd_order_mealbox');



            // Calculate updated count
            $newCount = $currentHas + $mealboxGive - $mealboxPick;

            // Update the DB
            return DB::table('mrd_user')
                ->where('mrd_user_id', $userId)
                ->update(['mrd_user_has_mealbox' => $newCount]);
        }
    }

    public function mealboxActive($userId)
    {

        $mealboxActive = DB::table('mrd_user')
            ->where('mrd_user_id', $userId)
            ->value('mrd_user_mealbox');

        return $mealboxActive;
    }





    public function mealboxOrderStatus($userId, $menuId, $date)
    {
        // Query to retrieve mrd_order_status using DB facade
        $mealboxStatus = DB::table('mrd_order')
            ->where('mrd_order_user_id', $userId)
            ->where('mrd_order_menu_id', $menuId)
            ->where('mrd_order_date', $date)
            ->value('mrd_order_mealbox');

        return $mealboxStatus;
    }




    public function mealboxCashback($userId, $mealboxPicked)
    {

        $mealboxActive = $this->mealboxActive($userId);

        if ($mealboxActive) {
            $mealboxHas = $this->mealboxHas($userId);

            $mealboxReturn   = $mealboxHas - $mealboxPicked;
            if ($mealboxReturn   < 0) {
                $mealboxPrice = DB::table('mrd_setting')->value('mrd_setting_mealbox_price');
                $mboxReturnPrice = abs($mealboxReturn) * $mealboxPrice;

                $notifMessage = abs($mealboxReturn) . " mealboxes refunded, " . $mboxReturnPrice . " Tk credited.";

                //ADD MEALBOX REFUND CREDIT
                DB::table('mrd_user')
                    ->where('mrd_user_id', $userId)
                    ->increment('mrd_user_credit', $mboxReturnPrice);

                //PAYMENT INSERT FOR MEALBOX REFUND   
                PaymentService::paymentInsert(
                    $userId,
                    null,
                    $mboxReturnPrice,
                    'mealbox',
                    'paid',
                    'refund',
                    null,
                    'system',
                    null,
                    null,
                    null,
                    null
                );

                NotifService::notifInsert($userId, null, $notifMessage, 'order', 'cashback', null, null, null,  '0');
            }
        }
    }





    public function mealboxAssigned($userId, $orderId, $quantity) {}
    public function mealboxPick($userId, $orderId, $quantity) {}
    public function mealboxReturn($userId, $orderId, $quantity) {}
}
