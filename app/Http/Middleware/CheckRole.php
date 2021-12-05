<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $Role = $request->user()->role()->first();
        if ($Role) {
            $request->request->add([
                'scope' => $Role->role
            ]);
        }

        return $next($request);
    }
}
