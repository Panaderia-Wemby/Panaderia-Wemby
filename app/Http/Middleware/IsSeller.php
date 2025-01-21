<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
class IsSeller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->rol == 1) {
            return $next($request);
        }elseif(Auth::user()->rol == 2 || Auth::user()->rol == 3){
            return redirect("/home");
            // return Auth::user()->rol;
        }
        return redirect("/login");
    }
}
