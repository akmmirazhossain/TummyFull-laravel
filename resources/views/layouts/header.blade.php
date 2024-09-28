<div class="navbar flex justify-between">
    <a href="#">
        <img class="h-14 btn btn-ghost" src="{{ asset('public/assets/images/logo.png') }}" alt="Logo">
    </a>

    <div class="gap_akm">
        @php
            $user = session('user');
        @endphp
        <div> Welcome! {{ $user->mrd_user_first_name }}</div>

        <form action="{{ route('logout') }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-sm btn-active">Logout</button>
        </form>
    </div>
</div>
