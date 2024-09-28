@extends('layouts.main')

@section('title', 'Chef List')

@section('content')

    <div class="flex items-center ">
        <div class="h2_akm w-80 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#D7D7D7">
                <path
                    d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z" />
            </svg>
            <span>Recharge History</span>
        </div>
    </div>
    <div class="divider"></div>



    @if (isset($error))
        <p class="text-red-500">{{ $error }}</p>
    @else
        <table class="table table-zebra w-full">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date Recharged</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($walletRecharges as $recharge)
                    <tr>
                        <td>{{ $recharge->mrd_user_first_name }}</td>
                        <td>{{ $recharge->mrd_user_phone }}</td>
                        <td>৳{{ $recharge->mrd_payment_amount }}</td>
                        <td>{{ $recharge->mrd_payment_method }}</td>
                        <td>{{ \Carbon\Carbon::parse($recharge->mrd_payment_date_paid)->format('d M Y, h:i A') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif



@endsection
