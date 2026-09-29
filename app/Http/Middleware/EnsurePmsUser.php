<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Hanya pengguna yang sudah masuk (siswa atau guru) yang boleh membuka sistem. */
class EnsurePmsUser
{
    public function handle(Request $request, Closure $next)
    {
        if (! session('pms_user')) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
