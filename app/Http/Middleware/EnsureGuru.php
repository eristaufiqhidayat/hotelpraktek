<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Panel guru dan pengaturan hanya untuk guru. */
class EnsureGuru
{
    public function handle(Request $request, Closure $next)
    {
        if ((session('pms_user')['role'] ?? null) !== 'guru') {
            return redirect()->route('dashboard')->with('toast', ['Halaman ini hanya untuk guru.', 'err']);
        }

        return $next($request);
    }
}
