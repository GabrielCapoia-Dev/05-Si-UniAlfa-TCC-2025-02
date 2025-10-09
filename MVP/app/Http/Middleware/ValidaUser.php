<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidaUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user(); // Para verificar se o usuario ta autenticado 

        if (! $user || ! $user instanceof \App\Models\User) {
            return redirect()->route('filament.admin.auth.login');
        }

        return $next($request);
    }
}
