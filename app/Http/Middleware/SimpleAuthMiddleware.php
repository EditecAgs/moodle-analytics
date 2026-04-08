<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimpleAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Si ya está autenticado en sesión, pasa directo
        if (session('dashboard_auth') === true) {
            return $next($request);
        }

        // Si está enviando el formulario de login
        if ($request->isMethod('post') && $request->has('password')) {
            if ($request->password === env('DASHBOARD_PASSWORD')) {
                session(['dashboard_auth' => true]);
                return redirect()->intended(route('dashboard'));
            }
            return back()->withErrors(['password' => 'Contraseña incorrecta']);
        }

        // Si no está autenticado, muestra el login
        return response()->view('auth.login');
    }
}
