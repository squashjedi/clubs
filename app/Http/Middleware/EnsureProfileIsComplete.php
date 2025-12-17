<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            $needsName = empty($user->first_name) || empty($user->last_name);

            // Allow access to the profile-complete routes and logout even if incomplete
            if ($needsName && ! $request->routeIs([
                'settings.profile',
                'logout',
            ])) {
                return redirect()
                    ->route('settings.profile')
                    ->with('flash', 'Please update your First Name and Last Name before continuing.');
            }
        }

        return $next($request);
    }
}
