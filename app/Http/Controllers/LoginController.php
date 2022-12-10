<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LoginController extends Controller
{
    public function index()
    {
        return view('login.index');
    }

    /**
     * Handle an authentication attempt.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if(empty($user->account_verified_date)){
            return back()->with([
                'loginError'=> 'Akun belum diverifikasi',
                'email'=> $request->email,
            ]);
        }

        if($user->is_blocked == 1 && in_array($user->roles, array('ADMIN', 'MODERATOR'))){
            return back()->with([
                'loginError'=> 'Login Gagal',
                'email'=> $request->email,
            ]);
        }

        if (Auth::attempt($credentials)) {

            $request->session()->regenerate();

            $user->last_login = date('Y-m-d H:i:s');
            $user->save();

            return redirect()->intended('dashboard');
        }

        return back()->with([
            'loginError'=> 'Failed login',
            'email'=> $request->email,
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
