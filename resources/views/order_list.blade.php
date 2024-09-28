@extends('layouts.main')

@section('title', 'Chef List')

@section('content')

    <div class="flex items-center ">
        <div class="h2_akm w-80 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#e8eaed">
                <path
                    d="M320-280q17 0 28.5-11.5T360-320q0-17-11.5-28.5T320-360q-17 0-28.5 11.5T280-320q0 17 11.5 28.5T320-280Zm0-160q17 0 28.5-11.5T360-480q0-17-11.5-28.5T320-520q-17 0-28.5 11.5T280-480q0 17 11.5 28.5T320-440Zm0-160q17 0 28.5-11.5T360-640q0-17-11.5-28.5T320-680q-17 0-28.5 11.5T280-640q0 17 11.5 28.5T320-600Zm120 320h240v-80H440v80Zm0-160h240v-80H440v80Zm0-160h240v-80H440v80ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z" />
            </svg>
            <span>Order List </span>
        </div>
    </div>
    <div class="divider"></div>

    <table class="table table-zebra">
        <thead>
            <tr>
                <th>Order Date</th>
                <th>Name</th>
                <th>Qty</th>
                <th>Total Price</th>
                <th>Cash to Get</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Mealbox</th>

                <th>Date Inserted</th>


            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr class="hover">
                    <td>{{ Carbon\Carbon::parse($order->mrd_order_date)->format('jS, M') }}</td>
                    <td>{{ $order->user_name }}</td>
                    <td>{{ $order->mrd_order_quantity }}</td>
                    <td>{{ $order->mrd_order_total_price }}</td>
                    <td>{{ $order->mrd_order_cash_to_get }}</td>
                    <td>{{ $order->mrd_order_status }}</td>
                    <td>{{ $order->mrd_order_user_pay_status }}</td>
                    <td>{{ $order->mrd_order_mealbox }}</td>

                    <td>{{ Carbon\Carbon::parse($order->mrd_order_date_insert)->format('g:i a, jS M') }}</td>


                </tr>
            @endforeach
        </tbody>
    </table>




@endsection
