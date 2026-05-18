<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class IsBaker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        if ((int) $user->rol === 2 || (int) $user->rol === 3) {
            return $next($request);
        }

        if ((int) $user->rol === 1) {
            return redirect("/home");
        }

        return redirect("/login");
    }
}
