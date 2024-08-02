<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    function index()
    {
        return view('login');
    }

    function checklogin(Request $request)
    {
        $this->validate($request, [
            'email'   => 'required|email',
            'password'  => 'required|alphaNum|min:3'
        ]);


        // $user = User::where('mrd_user_email', $request->get('email'))->first();

        $password = DB::table('mrd_user')
            ->where('mrd_user_email', $request->get('email'))
            ->value('mrd_user_password');


        if ($password && $request->get('password')) {
            Auth::login($password);
            return redirect('main/successlogin');
        } else {
            return back()->with('error', 'Wrong Login Details');
        }

        // if ($password && Hash::check($request->get('password'), $password)) {
        //     // Auth::login($password);
        //     return redirect('main/successlogin');
        // } else {
        //     return back()->with('error', 'Wrong Login Details');
        // }
    }

    function successlogin()
    {
        return view('successlogin');
    }

    function logout()
    {
        Auth::logout();
        return redirect('main');
    }
}
