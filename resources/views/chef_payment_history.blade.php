   @extends('layouts.main')

   @section('title', 'Chef Payment')

   @section('content')


       <div class="flex items-center ">
           <div class="h2_akm w-80">
               <div class="h2_akm w-80 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                       viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                       <path stroke-linecap="round" stroke-linejoin="round"
                           d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                   </svg>
                   <span>Chef Payment History</span>
               </div>
           </div>
       </div>
       <div class="divider "></div>

       <div class="overflow-x-auto ">
           <table class="table table-zebra w-full">
               <thead>
                   <tr>
                       <th>User ID</th>
                       <th>Name</th>
                       <th>Amount</th>
                       <th>Status</th>


                       <th>Order Counts</th>
                       <th>Total Quantity </th>

                       <th>Method</th>

                       <th>Date Paid</th>
                   </tr>
               </thead>
               <tbody>
                   @foreach ($payments as $payment)
                       @if ($payment->mrd_payment_for === 'chef')
                           <tr class="hover">
                               <td>{{ $payment->mrd_payment_user_id }}</td>
                               <td>{{ $payment->mrd_user_first_name }}</td>
                               <td>৳{{ $payment->mrd_payment_amount }}</td>
                               <td>{{ $payment->mrd_payment_status }}</td>


                               <td>{{ $payment->order_count }}</td>
                               <td>{{ $payment->mrd_payment_order_quantity }}</td>


                               <td>{{ $payment->mrd_payment_method }}</td>

                               <td>{{ $payment->formatted_date_paid }}</td>
                           </tr>
                       @endif
                   @endforeach
               </tbody>
           </table>

       </div>



   @endsection
