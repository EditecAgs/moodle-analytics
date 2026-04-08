<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (session('autenticado')) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);

        if ($request->password === env('DASHBOARD_PASSWORD')) {
            session(['dashboard_auth' => true]);
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['password' => 'Contraseña incorrecta.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('autenticado');
        return redirect()->route('login');
    }
}