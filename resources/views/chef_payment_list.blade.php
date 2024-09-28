   @extends('layouts.main')

   @section('title', 'Chef Payment')

   @section('content')


       <div class="flex items-center ">
           <div class="h2_akm w-80">
               <div class="h2_akm w-80 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                       viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                       <path stroke-linecap="round" stroke-linejoin="round"
                           d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                   </svg>
                   <span>Chef Payment List</span>
               </div>
           </div>
       </div>
       <div class="divider "></div>

       <div class="overflow-x-auto ">
           <table class="table table-zebra">
               <!-- head -->
               <thead>
                   <tr>
                       <th>ID</th>
                       <th>Name</th>
                       <th>Phone</th>
                       <th>Address</th>
                       <th>Total Meal</th>
                       <th>Total Commission</th>
                       <th></th>
                   </tr>
               </thead>
               <tbody>
                   @if ($chefs->isEmpty())
                       <tr>
                           <td colspan="7" class="text-center">No chefs require disbursement.</td>
                       </tr>
                   @else
                       @foreach ($chefs as $chef)
                           <tr class="hover">
                               <th>{{ $chef->mrd_user_id }}</th>
                               <td>{{ $chef->mrd_user_first_name }}</td>
                               <td>{{ $chef->mrd_user_phone }}</td>
                               <td>{{ $chef->mrd_user_address }}</td>
                               <td>
                                   {{ $chef->total_quantity }}
                                   <div class="badge badge-outline">L: {{ $chef->lunch_quantity }}</div>
                                   <div class="badge badge-outline">D: {{ $chef->dinner_quantity }}</div>
                               </td>
                               <td>৳{{ $chef->total_commission }}</td>
                               <td class="flex gap-1">
                                   <button
                                       onclick="openModal('{{ $chef->mrd_user_first_name }}', 
                    '{{ $chef->total_commission }}', 
                    '{{ $chef->mrd_user_payment_mfs }}', 
                    '{{ $chef->mrd_user_payment_phone }}', 
                    '{{ $chef->mrd_user_bank_info }}',
                    '{{ $chef->mrd_user_bank_account }}', 
                     '{{ $chef->mrd_user_id }}', 
                    '{{ $chef->total_quantity }}')"
                                       class="btn btn-primary btn-sm">Disburse Money</button>
                               </td>
                           </tr>
                       @endforeach
                   @endif



               </tbody>
           </table>
       </div>



       <!-- Modal -->
       <input type="checkbox" id="my-modal" class="modal-toggle" />
       <div class="modal">
           <div class="modal-box">
               <h2 class="h2_akm">Disperse Money</h2>
               <div class="divider"></div>
               <p><strong>Chef Name:</strong> <span id="modal-chef-name"></span></p>
               <p class="hidden"><span id="modal-user-id"></span></p>
               <p><strong>Total Commission:</strong></p>
               <p class="text-center">৳<span class="text-4xl" id="modal-commission"></span></p>
               <div class="divider"></div>

               <!-- Payment Method Options -->
               <div class="payment-methods">
                   <div>
                       <ul class="steps flex">
                           <li class="step step-primary flex-1">Login into bank acc.</li>
                           <li class="step step-primary flex-1">Send Money</li>
                           <li class="step step-primary flex-1">Confirm Payment</li>
                       </ul>
                   </div>
                   <div class="divider"></div>
                   <div class="form-control mt_akm" id="bank-info">
                       <div class="flex flex-row items-center">

                           <div>
                               <input type="radio" name="payment_method" value="bank" required
                                   class="radio checked:bg-blue-500 mr-4" />
                           </div>
                           <div>
                               <p class="h3_akm underline">Bank Transfer</p>
                               <p><strong>Bank Info:</strong> <span id="modal-bank_info"></span></p>
                               <p><strong>Bank Account Number:</strong> <span id="modal-bank_account"></span></p>
                           </div>
                       </div>
                   </div>

                   <div class="form-control" id="mfs-info">
                       <div class="flex flex-row items-center">
                           <div>
                               <input type="radio" name="payment_method" value="mfs" required
                                   class="radio checked:bg-red-500 mr-4" />
                           </div>
                           <div>
                               <p class="h3_akm underline">MFS (BKash/Nagad)</p>
                               <p><strong>MFS Name:</strong> <span id="modal-payment_mfs"></span></p>
                               <p><strong>MFS Phone Number:</strong> <span id="modal-payment_phone"></span></p>
                           </div>
                       </div>
                   </div>
               </div>

               <!-- Success Message -->
               <div class="success-message hidden">
                   <div role="alert" class="alert alert-success">
                       <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none"
                           viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                               d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                       </svg>
                       <span>Congratulations! Money disbursement information was successfully added to the database for this
                           chef.
                       </span>
                   </div>
               </div>

               <div class="modal-action">
                   <form id="payment-form" action="{{ route('chef-pay') }}" method="POST">
                       @csrf
                       <input type="hidden" id="chef-name-input" name="chef_name">
                       <input type="hidden" id="commission-input" name="commission">
                       <input type="hidden" id="user-id-input" name="user_id">
                       <input type="hidden" id="total-quantity" name="total_quantity">
                       <!-- Add this hidden input for the payment method -->
                       <input type="hidden" id="payment-method-input" name="payment_method">
                       <button type="submit" class="btn btn-success">Confirm Payment</button>
                   </form>
                   <label for="my-modal" class="btn">Close</label>
               </div>
           </div>
       </div>



       <script>
           function openModal(chefName, commission, payment_mfs, payment_phone, bank_info, bank_account, user_id,
               total_quantity) {
               // Set modal values
               document.getElementById('modal-chef-name').innerText = chefName;
               document.getElementById('modal-user-id').innerText = user_id;
               document.getElementById('modal-commission').innerText = commission;
               document.getElementById('modal-payment_mfs').innerText = payment_mfs;
               document.getElementById('modal-payment_phone').innerText = payment_phone;
               document.getElementById('modal-bank_info').innerText = bank_info;
               document.getElementById('modal-bank_account').innerText = bank_account;


               // Set form inputs
               document.getElementById('chef-name-input').value = chefName;
               document.getElementById('commission-input').value = commission;
               document.getElementById('user-id-input').value = user_id;
               document.getElementById('total-quantity').value = total_quantity;

               // Open the modal
               document.getElementById('my-modal').checked = true;
           }

           //REFRESH PAGE
           document.querySelector('.modal-action label').addEventListener('click', function() {
               location.reload(); // Refresh the page
           });



           //SUBMIT FORM DATA
           // SUBMIT FORM DATA
           document.getElementById('payment-form').addEventListener('submit', function(e) {
               e.preventDefault(); // Prevent the default form submission

               // Get the selected payment method (radio button)
               const selectedPaymentMethod = document.querySelector('input[name="payment_method"]:checked');

               // Check if a payment method is selected
               if (!selectedPaymentMethod) {
                   alert("Please select a payment method.");
                   return; // Stop the form submission if no payment method is selected
               }

               // Set the selected payment method in the hidden input field
               document.getElementById('payment-method-input').value = selectedPaymentMethod.value;

               // Prepare form data
               let formData = new FormData(this);

               // Submit the form with fetch
               fetch('{{ route('chef-pay') }}', {
                       method: 'POST',
                       headers: {
                           'X-CSRF-TOKEN': '{{ csrf_token() }}', // CSRF token for security
                       },
                       body: formData // Send the form data
                   })
                   .then(response => response.json())
                   .then(data => {
                       console.log(data); // Log the response data

                       // Hide the form and payment method options
                       document.getElementById('payment-form').style.display = 'none';
                       document.querySelector('.payment-methods').style.display = 'none';

                       // Show the success message
                       document.querySelector('.success-message').classList.remove('hidden');
                   })
                   .catch(error => console.error('Error:', error)); // Handle any errors
           });
       </script>


   @endsection
