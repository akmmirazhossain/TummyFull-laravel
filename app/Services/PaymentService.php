<?php

namespace App\Services;

use App\Services\MealboxService;


use Illuminate\Support\Facades\DB;




class PaymentService
{

  //MARK: Payment Insert
  public static function paymentInsert(
    $userId,
    $orderId = null,
    $amount,
    $paymentType = null,
    $paymentStatus = null,
    $paymentAction = null,
    $collectorId = null,
    $paymentMethod = null,
    $paymentMessage = null,
    $transactionId = null,
    $quantity = null,
    $datePaid = null
  ) {
    $insertData = [
      'mrd_payment_user_id'     => $userId,
      'mrd_payment_amount'      => $amount,
    ];

    if (!is_null($orderId)) {
      $insertData['mrd_payment_order_id'] = $orderId;
    }

    if (!is_null($paymentType)) {
      $insertData['mrd_payment_type'] = $paymentType;
    }

    if (!is_null($paymentStatus)) {
      $insertData['mrd_payment_status'] = $paymentStatus;
    }

    if (!is_null($paymentAction)) {
      $insertData['mrd_payment_action'] = $paymentAction;
    }

    if (!is_null($collectorId)) {
      $insertData['mrd_payment_collector_id'] = $collectorId;
    }

    if (!is_null($paymentMethod)) {
      $insertData['mrd_payment_method'] = $paymentMethod;
    }

    if (!is_null($paymentMessage)) {
      $insertData['mrd_payment_message'] = $paymentMessage;
    }

    if (!is_null($transactionId)) {
      $insertData['mrd_payment_transaction_id'] = $transactionId;
    }

    if (!is_null($quantity)) {
      $insertData['mrd_payment_order_quantity'] = $quantity;
    }

    if (!is_null($datePaid)) {
      $insertData['mrd_payment_date_paid'] = $datePaid;
    }

    return DB::table('mrd_payment')->insertGetId($insertData);
  }
}
