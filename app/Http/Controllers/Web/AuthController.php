<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function checklogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|alphaNum|min:3'
        ]);

        $password = DB::table('mrd_user')
            ->where('mrd_user_email', $request->get('email'))
            ->value('mrd_user_password');

        $mrd_user_type = DB::table('mrd_user')
            ->where('mrd_user_email', $request->get('email'))
            ->value('mrd_user_type');

        if (Hash::check($request->get('password'), $password)) {

            // Check if the user type is 'admin'
            if ($mrd_user_type === 'admin') {
                $request->session()->regenerate();
                $user = DB::table('mrd_user')->where('mrd_user_email', $request->get('email'))->first();
                $request->session()->put('user', $user);

                // Redirect to the success page
                return redirect('/dashboard');
            } else {
                // Handle non-admin user case
                return back()->with('error', 'You are not authorized to access this page.');
            }
        } else {
            return back()->with('error', 'Wrong Login Details');
        }
    }



    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect('/login');
    }
}
