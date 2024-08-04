@if (session()->has('user'))
    @php
        $user = session('user');
    @endphp

    {{-- HTML STARTS            --}}


    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Panel</title>
        <!-- Bootstrap CSS -->
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
        <style>
            .sidebar {
                height: 100vh;
                position: fixed;
                top: 0;
                left: 0;
                width: 250px;
                background-color: #f8f9fa;
                padding-top: 20px;
            }

            .content {
                margin-left: 250px;
                padding: 20px;
            }

            .navbar {
                margin-left: 250px;
            }
        </style>
    </head>

    <body>
        <?php
        
        echo 'hello';
        ?>
        <div class="sidebar">
            <h4 class="text-center">Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Recharge Wallet</a>
                </li>

            </ul>
        </div>

        <div class="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <a class="navbar-brand" href="#">Admin Panel</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="#">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container mt-4">
                <h1>Recharge Wallet</h1>
                <h5>Enter Customer's phone number that they have used for registration and the amount they have sent on
                    bkash/nagad.</h5>
                <br>
                <div id="responseMessage" class="h6"></div>

                <form method="post" id="rechargeForm" action="{{ url('/recharge-wallet') }}">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label>Customer's Phone Number</label>
                        <input type="text" name="phone" class="form-control" required />
                    </div>
                    <div class="form-group">
                        <label>Recharge Amount</label>
                        <input type="number" name="amount" class="form-control" required />
                    </div>
                    <div class="form-group">
                        <input type="submit" name="submit" class="btn btn-primary"
                            value="proceed to recharge wallet" />
                    </div>
                </form>



            </div>
        </div>

        <!-- Bootstrap JS and dependencies -->
        <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    </body>


    <script>
        document.getElementById('rechargeForm').addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent the default form submission

            const form = event.target;
            const formData = new FormData(form);

            const csrfToken = form.querySelector('input[name="_token"]').value;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData
                });

                const result = await response.json();

                // Handle the JSON response
                if (response.ok) {
                    document.getElementById('responseMessage').innerText =
                        `Success: ${result.message} (Phone: ${result.phone}, Amount: ${result.amount})`;
                    form.reset(); // Reset the form fields
                } else {
                    document.getElementById('responseMessage').innerText = `Error: ${result.message}`;
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('responseMessage').innerText =
                    'An error occurred while processing your request.';
            }
        });
    </script>



    </html>


    {{-- HTML ENDS --}}
@else
    <title>404</title>
    <div class="container">
        <h1>404</h1>
        <p>Oops! The page you are looking for does not exist.</p>
    </div>
@endif
