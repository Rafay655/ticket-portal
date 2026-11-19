<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DynamicPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the current action
        $action = $request->route()->getActionMethod();
        // Dump the action (e.g., "create", "edit", "destroy")

        // Perform any middleware checks (e.g., permissions)
        if ($action === 'create' && !$request->user()->can('role.create')) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
