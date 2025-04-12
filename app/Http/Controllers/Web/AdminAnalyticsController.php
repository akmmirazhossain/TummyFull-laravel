<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminAnalyticsController extends Controller
{
    public function dashboard(Request $request)
    {
        // Get data for orders per date chart
        $ordersPerDateData = $this->getOrdersPerDateData($request);

        // Get data for lunch/dinner per date chart
        $ldPerDateData = $this->getLDPerDateData();

        $userRegPerDay = $this->getUserRegPerDay();

        // Pass both sets of data to the view
        return view('dashboard', [
            'ordersPerDateData' => $ordersPerDateData,
            'ldPerDateData' => $ldPerDateData,
            'userRegPerDay' => $userRegPerDay
        ]);
    }


    public function getOrdersPerDateData(Request $request)
    {
        // Set default date range (e.g., all time if no date range is provided)
        // $dateRange = $request->input('date_range', 'all_time');
        $dateRange = 'this_month';

        // Determine the start and end dates based on the selected date range
        switch ($dateRange) {

            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;

            case 'last_2_weeks':
                $startDate = Carbon::now()->subWeeks(2)->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;

            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;

            case 'this_quarter':
                $startDate = Carbon::now()->firstOfQuarter();
                $endDate = Carbon::now()->lastOfQuarter();
                break;

            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;

            case 'all_time':
            default:
                $startDate = null;  // No start date for all time
                $endDate = null;    // No end date for all time
                break;
        }

        // Modify the query to filter by the date range if applicable
        $orderQuery = DB::table('mrd_order')
            ->select(
                DB::raw('DATE(mrd_order_date_insert) as order_day'),
                DB::raw('SUM(mrd_order_quantity) as total_quantity')
            )
            ->groupBy('order_day')
            ->orderBy('order_day', 'ASC');

        // Apply date range filter if a start date is specified
        if ($startDate && $endDate) {
            $orderQuery->whereBetween('mrd_order_date_insert', [$startDate, $endDate]);
        }

        $orderData = $orderQuery->get();


        $dates = [];
        $quantities = [];
        foreach ($orderData as $data) {
            $dates[] = Carbon::parse($data->order_day)->format('jS M'); // '19th Sep' format
            $quantities[] = $data->total_quantity;
        }

        return [
            'dates' => $dates,
            'quantities' => $quantities
        ];
    }



    public function getLDPerDateData()
    {
        // Set default date range
        $dateRange = 'this_month'; // You can replace this with $request->input('date_range', 'this_month');

        // Determine the start and end dates based on the selected date range
        switch ($dateRange) {
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;

            case 'last_2_weeks':
                $startDate = Carbon::now()->subWeeks(2)->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;

            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;

            case 'this_quarter':
                $startDate = Carbon::now()->firstOfQuarter();
                $endDate = Carbon::now()->lastOfQuarter();
                break;

            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;

            case 'all_time':
            default:
                $startDate = null;  // No start date for all time
                $endDate = null;    // No end date for all time
                break;
        }

        // Build the query with optional date filtering
        $query = "
        SELECT 
            DATE(mrd_order.mrd_order_date_insert) AS date,
            SUM(CASE WHEN mrd_menu.mrd_menu_period = 'lunch' THEN mrd_order.mrd_order_quantity ELSE 0 END) AS lunch_count,
            SUM(CASE WHEN mrd_menu.mrd_menu_period = 'dinner' THEN mrd_order.mrd_order_quantity ELSE 0 END) AS dinner_count
        FROM 
            mrd_order
        JOIN 
            mrd_menu ON mrd_order.mrd_order_menu_id = mrd_menu.mrd_menu_id
    ";

        // Add the WHERE clause if start and end dates are defined
        if ($startDate && $endDate) {
            $query .= " WHERE mrd_order.mrd_order_date_insert BETWEEN ? AND ?";
        }

        $query .= "
        GROUP BY 
            date
        ORDER BY 
            date ASC
    ";

        // Execute the query with parameters if date filtering is applied
        $orders = DB::select($query, $startDate && $endDate ? [$startDate, $endDate] : []);

        $dates = collect($orders)->pluck('date')->map(function ($date) {
            return Carbon::parse($date)->format('jS M'); // Convert to '19th Sep' format
        });

        $lunchQuantities = collect($orders)->pluck('lunch_count');
        $dinnerQuantities = collect($orders)->pluck('dinner_count');

        return [
            'dates' => $dates,
            'lunchQuantities' => $lunchQuantities,
            'dinnerQuantities' => $dinnerQuantities
        ];
    }



    public function getUserRegPerDay()
    {
        // Set default date range
        $dateRange = 'this_year'; // You can replace this with $request->input('date_range', 'this_month');

        // Determine the start and end dates based on the selected date range
        switch ($dateRange) {
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;

            case 'last_2_weeks':
                $startDate = Carbon::now()->subWeeks(2)->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;

            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;

            case 'this_quarter':
                $startDate = Carbon::now()->firstOfQuarter();
                $endDate = Carbon::now()->lastOfQuarter();
                break;

            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;

            case 'all_time':
            default:
                $startDate = null;  // No start date for all time
                $endDate = null;    // No end date for all time
                break;
        }

        // Fetch user registrations per day
        $userRegistrations = DB::table('mrd_user')
            ->select(DB::raw('DATE(mrd_user_date_added) as date'), DB::raw('COUNT(*) as count'))
            ->when($startDate, function ($query) use ($startDate) {
                return $query->where('mrd_user_date_added', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                return $query->where('mrd_user_date_added', '<=', $endDate);
            })
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Prepare the response arrays
        $dates = [];
        $quantities = [];

        foreach ($userRegistrations as $registration) {
            $dates[] = $registration->date;
            $quantities[] = $registration->count;
        }



        return [
            'dates' => array_map(fn($date) => Carbon::parse($date)->format('jS M'), $dates),
            'userQuantities' => $quantities
        ];
    }

    // public function lunch_dinner_per_day()
    // {
    //     // Fetch order data based on the date and menu period (lunch/dinner)
    //     $orders = DB::table('mrd_order')
    //         ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
    //         ->select(
    //             DB::raw('DATE(mrd_order.mrd_order_date_insert) as date'),
    //             DB::raw('SUM(CASE WHEN mrd_menu.mrd_menu_period = "lunch" THEN mrd_order.mrd_order_quantity ELSE 0 END) as lunch_count'),
    //             DB::raw('SUM(CASE WHEN mrd_menu.mrd_menu_period = "dinner" THEN mrd_order.mrd_order_quantity ELSE 0 END) as dinner_count')
    //         )
    //         ->groupBy('date')
    //         ->orderBy('date', 'ASC')
    //         ->get();

    //     // Extract dates, lunch quantities, and dinner quantities
    //     $dates = $orders->pluck('date');
    //     $lunchQuantities = $orders->pluck('lunch_count');
    //     $dinnerQuantities = $orders->pluck('dinner_count');

    //     return view('dashboard', [
    //         'dates' => $dates,
    //         'lunchQuantities' => $lunchQuantities,
    //         'dinnerQuantities' => $dinnerQuantities
    //     ]);
    // }



    // public function getOrderDataForChart()
    // {
    //     // Fetch order data based on the date and menu period (lunch/dinner)
    //     $orders = DB::table('mrd_order')
    //         ->join('mrd_menu', 'mrd_order.mrd_order_menu_id', '=', 'mrd_menu.mrd_menu_id')
    //         ->select(
    //             DB::raw('DATE(mrd_order.mrd_order_date_insert) as date'),
    //             DB::raw('SUM(CASE WHEN mrd_menu.mrd_menu_period = "lunch" THEN mrd_order.mrd_order_quantity ELSE 0 END) as lunch_count'),
    //             DB::raw('SUM(CASE WHEN mrd_menu.mrd_menu_period = "dinner" THEN mrd_order.mrd_order_quantity ELSE 0 END) as dinner_count')
    //         )
    //         ->groupBy('date')
    //         ->orderBy('date', 'ASC')
    //         ->get();

    //     return view('chart', compact('orders'));
    // }



    public function order_list()
    {
        $sql = "
        SELECT 
            mrd_order.mrd_order_quantity,
            mrd_order.mrd_order_total_price,
            mrd_order.mrd_order_cash_to_get,
            mrd_order.mrd_order_status,
            mrd_order.mrd_order_user_pay_status,
            mrd_order.mrd_order_mealbox,
            mrd_order.mrd_order_date_insert,
            mrd_order.mrd_order_date,
            mrd_user.mrd_user_first_name as user_name
        FROM 
            mrd_order
        JOIN 
            mrd_user ON mrd_order.mrd_order_user_id = mrd_user.mrd_user_id
            ORDER BY mrd_order.mrd_order_date_insert DESC
    ";

        $orders = DB::select($sql);

        return view('order_list', compact('orders'));
    }
}
