@extends('layouts.main')

@section('title', 'Chef List')

@section('content')

    <div class="flex items-center ">
        <div class="h2_akm w-80 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#D7D7D7">
                <path
                    d="M200-200v-560 560Zm0 80q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v100h-80v-100H200v560h560v-100h80v100q0 33-23.5 56.5T760-120H200Zm320-160q-33 0-56.5-23.5T440-360v-240q0-33 23.5-56.5T520-680h280q33 0 56.5 23.5T880-600v240q0 33-23.5 56.5T800-280H520Zm280-80v-240H520v240h280Zm-160-60q25 0 42.5-17.5T700-480q0-25-17.5-42.5T640-540q-25 0-42.5 17.5T580-480q0 25 17.5 42.5T640-420Z" />
            </svg>
            <span>Wallet Recharge</span>
        </div>
    </div>
    <div class="divider"></div>

    <div class="overflow-x-auto ">
        <div class="space-y-4 ">
            <div class="h3_akm">Steps to recharge wallet:</div>
            <div class="flex">
                <ul class="steps steps-vertical lg:steps-horizontal w-full">
                    <li class="step step-primary">After Receiving Money in Bkash/Nagad</li>
                    <li class="step step-primary">Search Phone Number here</li>
                    <li class="step step-primary">Enter Amount</li>
                    <li class="step step-primary">Click Recharge</li>
                </ul>
            </div>
        </div>
        <div class="divider"></div>
        <div class="space-y-4 ">
            <div class="h3_akm">Search Phone Number:</div>
            <div>
                <div>
                    <input type="text" id="user_phone" placeholder="017 _ _ _ _ _ _ _ _ _"
                        class="input input-bordered input-lg w-full max-w-xs" />
                    <div id="user_results" class="mt-2"></div> <!-- Placeholder for results -->
                </div>
            </div>
        </div>
    </div>

    <!-- DaisyUI Modal -->
    <div id="rechargeModal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Recharge Wallet</h3>
            <p>ID: <span id="modal_user_id"></span></p>
            <p>Name: <span id="modal_user_name"></span></p>
            <p>Current Credit: <span id="modal_user_credit"></span></p>
            <p>Phone: <span id="modal_user_phone"></span></p>

            <!-- New: Input field for BDT amount -->
            <div>
                <label for="recharge_amount" class="block">Enter BDT Amount:</label>
                <input type="number" id="recharge_amount" class="input input-bordered w-full" required min="1"
                    placeholder="Enter amount in BDT" />
            </div>

            <!-- New: Submit button to trigger AJAX call -->
            <div class="modal-action">
                <button id="submitRecharge" class="btn btn-success">Recharge</button>
                <button class="btn btn-error" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Close Modal Function
        function closeModal() {
            $('#rechargeModal').removeClass('modal-open');
        }

        $(document).on('keyup', '#user_phone', function() {
            let userPhone = $(this).val();

            if (userPhone.length >= 4) {
                $.ajax({
                    url: "{{ route('wallet-search-user') }}",
                    type: "GET",
                    data: {
                        phone: userPhone
                    },
                    success: function(response) {
                        $('#user_results').empty(); // Clear previous results

                        if (response.success && response.data.length > 0) {
                            // Iterate through the response data
                            response.data.forEach(function(user) {
                                $('#user_results').append(`
                                    <div class="user-item border-b">
                                        <p>ID: ${user.mrd_user_id}</p>
                                        <p>Name: ${user.mrd_user_first_name}</p>
                                        <p>Current Credit: ${user.mrd_user_credit}</p>
                                        <p>Phone: ${user.mrd_user_phone}</p>
                                        <button class="btn btn-info" data-id="${user.mrd_user_id}" data-name="${user.mrd_user_first_name}" data-credit="${user.mrd_user_credit}" data-phone="${user.mrd_user_phone}">Recharge Wallet</button>
                                    </div>`);
                            });
                        } else {
                            $('#user_results').append('<p>No data found</p>');
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            } else {
                $('#user_results').empty(); // Clear results if less than 4 digits
            }
        });

        // Open modal when Recharge Wallet button is clicked
        $(document).on('click', '.btn-info', function() {
            let userID = $(this).data('id');
            let userName = $(this).data('name');
            let userCredit = $(this).data('credit');
            let userPhone = $(this).data('phone');

            // Set the modal content
            $('#modal_user_id').text(userID);
            $('#modal_user_name').text(userName);
            $('#modal_user_credit').text(userCredit);
            $('#modal_user_phone').text(userPhone);

            // Open the modal
            $('#rechargeModal').addClass('modal-open');
        });



        // New: Recharge button click event
        $(document).on('click', '#submitRecharge', function() {
            let userID = $('#modal_user_id').text();
            let phone = $('#modal_user_phone').text();
            let amount = $('#recharge_amount').val();

            if (amount > 0) { // Ensure amount is valid
                $.ajax({
                    url: "{{ route('wallet-recharge-confirm') }}", // Define this route in your web.php
                    type: "POST",
                    data: {
                        user_id: userID,
                        phone: phone,
                        amount: amount,
                        _token: "{{ csrf_token() }}" // Ensure CSRF token for security
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Wallet recharged successfully!');
                            closeModal();
                            window.location.href = "{{ route('wallet-recharge-history') }}"
                        } else {
                            alert('Recharge failed!');
                        }
                    },
                    error: function(error) {
                        console.log(error);
                    }
                });
            } else {
                alert('Please enter a valid amount.');
            }
        });
    </script>

@endsection
