<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Premissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission  The required permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        // Get the authenticated user
        // dd($permission);
        $user = auth()->user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['error' => 'Unauthorized: User is not logged in'], 401);
        }

        

        // Check if the authenticated user has roles
        if ($user->roles->isEmpty()) {
            return response()->json(['error' => 'User has no roles'], 403);
        }

        // Check if the authenticated user has the permission
        if (!$user->hasPermission($permission)) {
            return response()->json(['error' => 'Unauthorized: Insufficient permissions'], 403);
        }

        return $next($request);
    }
}
