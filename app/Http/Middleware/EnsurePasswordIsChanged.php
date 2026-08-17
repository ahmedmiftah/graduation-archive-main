<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Force accounts created with a temporary password (students, and later
     * supervisors) through the password-change page before anything else.
     * Users without the flag (everyone pre-existing) are entirely unaffected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->force_password_change) {
            return $next($request);
        }

        if ($request->routeIs('password.edit', 'password.update', 'logout')) {
            return $next($request);
        }

        return redirect()->route('password.edit')
            ->with('status', 'يجب تغيير كلمة المرور المؤقتة قبل المتابعة');
    }
}
