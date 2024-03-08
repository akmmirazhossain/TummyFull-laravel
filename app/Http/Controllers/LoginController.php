<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


class LoginController extends Controller
{
    // Display the login form
    public function showLoginForm()
    {
        return view('auth.login'); // Assuming you have a login.blade.php file in resources/views/auth
    }

    // Handle the login request
    public function login(Request $request)
    {
        // Validate the login request
        $request->validate([
            'mrd_user_email' => 'required|email',
            'password' => 'required',
        ]);

        // dd(Hash::make($request->input('password')));
        // Attempt to authenticate the user
        $credentials = $request->only('mrd_user_email', 'password');
        // $credentials['password'] = bcrypt($request->input('password'));

        // if (Auth::attempt($credentials)) {
        //     // Authentication successful
        //     return redirect()->intended('/dashboard');
        // }

        $user = User::where('mrd_user_email', $request->input('mrd_user_email'))->first();

        if ($user && Hash::check($request->input('password'), $user->mrd_user_password)) {
            // Authentication successful
            Auth::login($user);
            return redirect()->intended('login')->with('success', 'Login successful!');
            //return back()->intended('/dashboard')->with('success', 'Login successful!');
        } else {
            // Authentication failed
            return back()->withErrors(['loginError' => 'Invalid credentials'])->withInput($request->only('mrd_user_email'));
        }


        // Authentication failed
        //return back()->withErrors(['loginError' => 'Invalid credentials'])->withInput($request->only('mrd_user_email'));
    }

    // Logout the user
    public function logout()
    {
        Auth::logout();
        return redirect('/login');
    }
}
