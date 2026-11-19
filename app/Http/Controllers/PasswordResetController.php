<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // Send OTP via Redis
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->input('email');

        // Generate OTP and token
        $otp = random_int(100000, 999999);
        $token = Str::random(60);

        // Store OTP and token in Redis with a TTL of 5 minutes
        Redis::setex("password_reset:otp:$email", 300, $otp); // Key expires in 5 minutes
        Redis::setex("password_reset:token:$email", 300, $token);

        // Send the OTP to the user via email
        Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($email) {
            $message->to($email)->subject('Your Password Reset OTP');
        });

        return redirect()->route('password.request', ['email' => $email, 'token' => $token])
            ->with('status', 'OTP sent to your email. It is valid for 5 minutes.');
    }

    // Show OTP form
    public function showOtpForm(Request $request)
    {
        return view('auth.otp');
    }

    // Verify OTP
    public function verifyAndShowPasswordResetForm(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required',
            'token' => 'nullable',
        ]);
    
        $email = $request->input('email');
        $otp = $request->input('otp');
        $token = $request->input('token');
        
        logger('Email:', [$email]);
        logger('OTP:', [$otp]);
        logger('Token:', [$token]);
    
        // Retrieve OTP from Redis
        $cachedOtp = Redis::get("password_reset:otp:$email");
    
        if (!$cachedOtp || $cachedOtp !== $otp) {
            logger('False 1');
            return response()->json(['error' => 'Invalid or expired OTP. Please try again.'], 400);
        }
        
        // If the OTP is valid, retrieve the token from Redis
        $cachedToken = Redis::get("password_reset:token:$email");
        
        if (!$cachedToken || ($token && $cachedToken !== $token)) {
            logger('False 2');
            return response()->json(['error' => 'Invalid or expired token.'], 400);
        }
    
        logger('True');
        
        // Return JSON response with the redirect URL
        return response()->json([
            'message' => 'OTP verified successfully!',
            'redirect_url' => route('password.reset.form', ['email' => $email, 'token' => $cachedToken]),
        ]);
    }
    public function showPasswordResetForm(Request $request)
    {
        $email = $request->query('email');
        $token = $request->query('token');
    
        return view('auth.password_reset', compact('email', 'token'));
    }
    

    // Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
            'token' => 'required',
        ]);
    
        $email = $request->input('email');
        $token = $request->input('token');
    
        // Validate token from Redis
        $cachedToken = Redis::get("password_reset:token:$email");
        if (!$cachedToken || $cachedToken !== $token) {
            return redirect()->route('password.request')->withErrors(['email' => 'Invalid or expired token.']);
        }
    
        // Update user's password
        $user = User::where('email', $email)->first();
        $user->password = Hash::make($request->input('password'));
        $user->save();
    
        // Clear OTP and token from Redis
        Redis::del("password_reset:otp:$email");
        Redis::del("password_reset:token:$email");
    
        return redirect()->route('login')->with('status', 'Your password has been reset successfully.');
    }
    

    // Resend OTP
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $email = $request->input('email');
        $otp = random_int(100000, 999999);

        // Store OTP in Redis with a TTL of 5 minutes
        Redis::setex("password_reset:otp:$email", 300, $otp);

        Mail::send('emails.otp', ['otp' => $otp], function ($message) use ($email) {
            $message->to($email)->subject('Your Password Reset OTP');
        });

        return response()->json(['message' => 'A new OTP has been sent to your email.']);
    }
}
