@if (session()->has('user'))
    <!DOCTYPE html>
    <html data-theme="forest">

    <head>
        <title>@yield('title')</title>
        {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}


        {{-- <link rel="stylesheet" href="{{ asset('build/assets/app-4e5e7dd6.css') }}"> --}}
        @vite('resources/css/app.css')
        @vite('resources/js/app.js')

    </head>

    <body class="">

        {{-- HEADER --}}
        <header class="mx-auto">
            @include('layouts.header')
        </header>


        <div class=" grid grid-cols-12  mx-auto gap_akm min-h-screen">
            {{-- LEFT NAV --}}
            <div class=" col-span-2 ">
                @include('layouts.leftnav')
            </div>

            {{-- MAIN CONTENT --}}
            <div class="col-span-10  pad_akm border border-ghost  rounded-lg">
                @yield('content')
            </div>
        </div>

        <footer>
            @include('layouts.footer')
        </footer>

    </body>

    </html>
@else
    <h1>404</h1>
    <p>Oops! The page you are looking for does not exist.</p>
@endif
