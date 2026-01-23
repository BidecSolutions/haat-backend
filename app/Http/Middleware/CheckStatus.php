<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
class CheckStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth()->user();
            if ($user->status) {
                return $next($request);
            } else {
                return redirect()->route('user.authorization');
            }
        }
        abort(403);
    }
}
