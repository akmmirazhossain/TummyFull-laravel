@extends('layouts.main')

@section('title', 'Notifications')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div class="h2_akm flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#e8eaed">
            <path d="M160-200v-80h80v-280q0-83 50-147.5T420-792v-28q0-25 17.5-42.5T480-880q25 0 42.5 17.5T540-820v28q80 20 130 84.5T720-560v280h80v80H160Zm320-300Zm0 420q-33 0-56.5-23.5T400-160h160q0 33-23.5 56.5T480-80ZM320-280h320v-280q0-66-47-113t-113-47q-66 0-113 47t-47 113v280Z" />
        </svg>
        <span>Notifications</span>
    </div>
</div>

<div class="divider"></div>

<div class="overflow-x-auto mt-4">
    <table class="table table-zebra w-full">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Seen?</th>
                <th>Message</th>
                <th>Quantity</th>
                <th>Total Price</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($notifications as $notif)
            <tr>
                <td>{{ $notif->mrd_notif_id }}</td>
                <td>{{ $notif->mrd_user_first_name }} {{ $notif->mrd_user_last_name }}</td>
                <td>
                    @if ($notif->mrd_notif_seen)
                    <span class="badge badge-success">Seen</span>
                    @else
                    <span class="badge badge-warning">Unseen</span>
                    @endif
                </td>
                <td>{{ $notif->mrd_notif_message }}</td>
                <td>{{ $notif->mrd_notif_quantity }}</td>
                <td>₹{{ number_format($notif->mrd_notif_total_price, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($notif->mrd_notif_date_added)->format('M j, Y H:i') }}</td>
            </tr>

            @empty

            <tr>
                <td colspan="7" class="text-center">No notifications found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection