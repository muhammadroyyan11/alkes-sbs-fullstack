<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(403, 'Akses ditolak. Silakan login terlebih dahulu.');
        }

        $user = auth()->user();

        if (!$user->isStaff()) {
            abort(403, 'Akses ditolak. Hanya admin yang diizinkan.');
        }

        if (!$user->canAccessAdminRoute($request->route()?->getName())) {
            abort(403, 'Akses ditolak. Role ' . ucfirst($user->role) . ' tidak memiliki akses ke menu ini.');
        }

        return $next($request);
    }
}
