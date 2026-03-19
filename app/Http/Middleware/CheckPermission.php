<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        if (Gate::denies($permission)) {
            session()->flash('error', __('You do not have permission to access this page.'));

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
