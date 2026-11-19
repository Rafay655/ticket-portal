<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class ValidatePasswordResetToken
{
    public function handle($request, Closure $next)
    {
        $email = $request->query('email');
        $token = $request->query('token');
    
        // Debugging
        logger('Email:', [$email]);
        logger('Token:', [$token]);
        logger('Cached Token:', [Cache::get('password_reset_token_' . $email)]);
    
        if (!$email || !$token || Cache::get('password_reset_token_' . $email) !== $token) {
            logger('Validation failed');
            return redirect()->route('password.request')->withErrors(['email' => 'Invalid or expired token.']);
        }
    
        logger('Validation succeeded');
        return $next($request);
    }
    
}
