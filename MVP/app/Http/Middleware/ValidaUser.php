<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidaUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user instanceof \App\Models\User) {
            return redirect()->route('filament.admin.auth.login');
        }

        return $next($request);
    }
}
