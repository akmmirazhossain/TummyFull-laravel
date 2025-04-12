@extends('layouts.main')

@section('title', 'User Details')

@section('content')

<div class="flex items-center">
    <div class="h2_akm w-80 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" height="28px" viewBox="0 -960 960 960" width="28px" fill="#e8eaed">
            <path
                d="m160-419 101-101-101-101L59-520l101 101Zm540-21 100-160 100 160H700Zm-220-40q-50 0-85-35t-35-85q0-51 35-85.5t85-34.5q51 0 85.5 34.5T600-600q0 50-34.5 85T480-480Zm0-160q-17 0-28.5 11.5T440-600q0 17 11.5 28.5T480-560q17 0 28.5-11.5T520-600q0-17-11.5-28.5T480-640Zm0 40ZM0-240v-63q0-44 44.5-70.5T160-400q13 0 25 .5t23 2.5q-14 20-21 43t-7 49v65H0Zm240 0v-65q0-65 66.5-105T480-450q108 0 174 40t66 105v65H240Zm560-160q72 0 116 26.5t44 70.5v63H780v-65q0-26-6.5-49T754-397q11-2 22.5-2.5t23.5-.5Zm-320 30q-57 0-102 15t-53 35h311q-9-20-53.5-35T480-370Zm0 50Z" />
        </svg>
        <span>User Details</span>
    </div>
</div>
<div class="divider"></div>
<div class="form-control">
    <label class="label">
        <span class="label-text">Full Address</span>
    </label>
    <input
        type="text"
        id="addressInput"
        placeholder="e.g. Flat 3A, House 42, Road 12, Block B, Downtown"
        class="input input-bordered">
    <div id="addressPreview" class="mt-2 text-sm text-gray-500"></div>
</div>

<script>
    document.getElementById('addressInput').addEventListener('input', function(e) {
        const parts = {
            flat: extractPattern(e.target.value, /flat\s*(\w+)/i),
            house: extractPattern(e.target.value, /house\s*(\w+)/i),
            road: extractPattern(e.target.value, /road\s*(\w+)/i),
            block: extractPattern(e.target.value, /block\s*(\w+)/i),
            area: e.target.value.split(',').pop().trim() // Last segment as fallback
        };

        document.getElementById('addressPreview').innerHTML = `
    Structured as: 
    Flat ${parts.flat || '--'}, 
    House ${parts.house || '--'}, 
    Road ${parts.road || '--'}, 
    Block ${parts.block || '--'}, 
    ${parts.area || '--'}
  `;
    });

    function extractPattern(text, regex) {
        const match = text.match(regex);
        return match ? match[1] : null;
    }
</script>


<div class="overflow-x-auto mt-4">
    <table class="table table-zebra">
        <tbody>
            <tr>
                <th>ID</th>
                <td>{{ $user->mrd_user_id }}</td>
            </tr>

            <tr>
                <th>Name</th>
                <td>{{ $user->mrd_user_first_name }} {{ $user->mrd_user_last_name }}</td>
            </tr>
            <tr>
                <th>Phone</th>
                <td>{{ $user->mrd_user_phone }}</td>
            </tr>
            <tr>
                <th>Address</th>
                <td>{{ $user->mrd_user_address }}</td>
            </tr>

            <tr>
                <th>Type</th>
                <td>{{ $user->mrd_user_type }}</td>
            </tr>
            <tr>
                <th>Credit</th>
                <td>₹{{ $user->mrd_user_credit }}</td>
            </tr>
            <tr>
                <th>Total Orders</th>
                <td>{{ $orderCount }}</td>
            </tr>

            <tr>
                <th> Orders delivered</th>
                <td>{{ $user->mrd_user_order_delivered }}</td>
            </tr>

            <tr>
                <th>Account created</th>

                <td>{{ \Carbon\Carbon::parse($user->mrd_user_date_added)->format('M j, Y') }}


                </td>
            </tr>


        </tbody>
    </table>
</div>


@endsection