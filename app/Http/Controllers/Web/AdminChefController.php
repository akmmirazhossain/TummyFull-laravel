<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminChefController extends Controller
{
    public function chef_payment_list(Request $request)
    {
        try {
            // Define the date range
            // $startDate = '2024-01-01';
            // $endDate = '2024-11-01';
            //  AND orders.mrd_order_date BETWEEN ? AND ?
            //$chefs = DB::select($sql, [$startDate, $endDate, $orderStatus, $paymentStatus]);
            //$endDate = Carbon::tomorrow()->toDateString();
            // Define the order status
            $orderStatus = 'delivered';
            $paymentStatus = 'unpaid';

            // Define the raw SQL query
            $sql = "
     SELECT 
        chefs.mrd_user_first_name,
        chefs.mrd_user_id,
        chefs.mrd_user_address,
        chefs.mrd_user_phone,
        chefs.mrd_user_email,
        chefs.mrd_user_bank_info,
        chefs.mrd_user_bank_account,
        chefs.mrd_user_payment_mfs,
        chefs.mrd_user_payment_phone,
        SUM(orders.mrd_order_quantity) AS total_quantity,
        SUM(CASE WHEN menu.mrd_menu_period = 'lunch' THEN orders.mrd_order_quantity ELSE 0 END) AS lunch_quantity,
        SUM(CASE WHEN menu.mrd_menu_period = 'dinner' THEN orders.mrd_order_quantity ELSE 0 END) AS dinner_quantity,
        SUM(orders.mrd_order_quantity) * settings.mrd_setting_commission_chef AS total_commission
    FROM 
        mrd_user AS chefs
        LEFT JOIN mrd_user AS customers ON chefs.mrd_user_id = customers.mrd_user_chef_id
        LEFT JOIN mrd_order AS orders ON customers.mrd_user_id = orders.mrd_order_user_id
        LEFT JOIN mrd_menu AS menu ON orders.mrd_order_menu_id = menu.mrd_menu_id
        LEFT JOIN mrd_setting AS settings ON 1 = 1 
    WHERE 
        chefs.mrd_user_type = 'chef'
      
        AND orders.mrd_order_status = ?
        AND orders.mrd_order_chef_pay_status = ?
        
    GROUP BY 
        chefs.mrd_user_id, chefs.mrd_user_first_name, chefs.mrd_user_address, chefs.mrd_user_email, settings.mrd_setting_commission_chef
";

            // Execute the query and get results
            $chefs = DB::select($sql, [$orderStatus, $paymentStatus]);

            // Convert results to collection
            $chefs = collect($chefs);

            // Pass the list of chefs to the view
            return view('chef_payment_list', ['chefs' => $chefs]);
        } catch (\Exception $e) {
            // Handle potential errors
            return back()->withErrors(['error' => 'Something went wrong!']);
        }
    }


    public function chef_pay(Request $request)
    {
        // Capture the data from the request
        $chefName = $request->input('chef_name');
        $commission = $request->input('commission');
        $paymentMethod = $request->input('payment_method');
        $total_quantity = $request->input('total_quantity');
        $chef_id = $request->input('user_id');

        // SQL query to select orders related to those users
        $sqlOrders = 'SELECT mrd_order.mrd_order_id, mrd_order.mrd_order_quantity
              FROM mrd_order
              JOIN mrd_user ON mrd_user.mrd_user_id = mrd_order.mrd_order_user_id
              WHERE mrd_user.mrd_user_chef_id = ? 
              AND mrd_order.mrd_order_chef_pay_status = "unpaid"';
        $orders = DB::select($sqlOrders, [$chef_id]);


        // Extract order IDs and quantities
        $orderIdList = array_column($orders, 'mrd_order_id');
        $orderIdString = implode(',', $orderIdList);

        $totalQuantity = array_sum(array_column($orders, 'mrd_order_quantity'));

        $commissionSetting = DB::table('mrd_setting')
            ->value('mrd_setting_commission_chef');
        $totalAmount = $totalQuantity *  $commissionSetting;

        // Insert payment record
        DB::table('mrd_payment')->insert([
            'mrd_payment_status' => 'paid',
            'mrd_payment_amount' => $totalAmount,
            'mrd_payment_user_id' => $chef_id,
            'mrd_payment_order_id' => $orderIdString,
            'mrd_payment_order_quantity' => $total_quantity,
            'mrd_payment_for' => 'chef',
            'mrd_payment_method' => $paymentMethod,
            'mrd_payment_date_paid' => now(), // Assuming you want to record the current date and time
        ]);

        //UPDATE TO "PAID"
        DB::table('mrd_order')
            ->whereIn('mrd_order_id', $orderIdList)
            ->update(['mrd_order_chef_pay_status' => 'paid']);

        // Return a JSON response to the frontend
        return response()->json([
            'message' => 'success',
            'chef_name' => $chefName,
            'commission' => $commission,
            'chef_id' => $chef_id,
            'payment_method' => $paymentMethod,
            'order_ids' => $orderIdString, // Include the order IDs as a comma-separated string
            'total_amount' => $totalAmount,
            'total_quantity' => $totalQuantity, // Include the total amount
        ]);
    }

    public function chef_list(Request $request)
    {
        $chefs = DB::select("
        SELECT mrd_user.*, mrd_area.mrd_area_name, COALESCE(SUM(mrd_payment.mrd_payment_amount), 0) AS total_payment
        FROM mrd_user
        LEFT JOIN mrd_area ON mrd_user.mrd_user_area = mrd_area.mrd_area_id
        LEFT JOIN mrd_payment ON mrd_user.mrd_user_id = mrd_payment.mrd_payment_user_id
        WHERE mrd_user.mrd_user_type = 'chef'
        GROUP BY mrd_user.mrd_user_id, mrd_area.mrd_area_name
    ");

        return view('chef_list', ['chefs' => $chefs]);
    }




    public function chef_payment_history(Request $request)
    {
        $payments = DB::select("
        SELECT 
            payment.mrd_payment_id,
            payment.mrd_payment_status,
            payment.mrd_payment_amount,
            payment.mrd_payment_user_id,
            payment.mrd_payment_order_id,
            payment.mrd_payment_order_quantity,
            payment.mrd_payment_for,
            payment.mrd_payment_collector_id,
            payment.mrd_payment_discount,
            payment.mrd_payment_method,
            payment.mrd_payment_message,
            payment.mrd_payment_date_paid,
            user.mrd_user_first_name,
            LENGTH(payment.mrd_payment_order_id) - LENGTH(REPLACE(payment.mrd_payment_order_id, ',', '')) + 1 AS order_count
        FROM 
            mrd_payment AS payment
        JOIN 
            mrd_user AS user
        ON 
            payment.mrd_payment_user_id = user.mrd_user_id
        WHERE 
            payment.mrd_payment_for = 'chef'
        ORDER BY 
            payment.mrd_payment_date_paid DESC
    ");

        // Format the date
        foreach ($payments as $payment) {
            $payment->formatted_date_paid = Carbon::parse($payment->mrd_payment_date_paid)->format('jS M Y, h:i A');
        }

        return view('chef_payment_history', ['payments' => $payments]);
    }
}
