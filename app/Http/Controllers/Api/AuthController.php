<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
// App\Http\Controllers\Api\AuthController.php
use App\Models\Employee; 
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    // POST /api/auth/login
    public function firstlogin(Request $request)
    {
        try {
            // Accept either "redirect_url" or "url" from client
            $request->merge([
                'redirect_url' => $request->input('redirect_url', $request->input('url')),
            ]);
    
            // --- helpers (scoped closures) ---
            $normalize = function (?string $url): ?string {
                if (!$url) return null;
                $parts = @parse_url($url);
                if (!$parts || empty($parts['scheme']) || empty($parts['host'])) return null;
    
                $scheme = strtolower($parts['scheme']);
                if (!in_array($scheme, ['http','https'], true)) return null;
    
                $host = strtolower($parts['host']);
                if (str_starts_with($host, 'www.')) $host = substr($host, 4);
    
                $path = $parts['path'] ?? '/';
                if ($path === '' || $path[0] !== '/') $path = '/'.ltrim($path ?? '/', '/');
                $path = rtrim($path, '/');
                if ($path === '') $path = '/';
    
                return $scheme.'://'.$host.$path; // no query/fragment
            };
    
            $isAllowed = function (?string $userUrl) use ($normalize): bool {
                if ($userUrl === null || $userUrl === '') return true; // optional param
                $clean = $normalize($userUrl);
                if ($clean === null) return false;
    
                // Pull all allowed base URLs and compare with same normalization
                $rows = DB::table('allowed_base_urls')->pluck('url');
                foreach ($rows as $base) {
                    $baseClean = $normalize($base);
                    if (!$baseClean) continue;
                    if ($clean === $baseClean || str_starts_with($clean, $baseClean.'/')) {
                        return true;
                    }
                }
                return false;
            };
            // --- end helpers ---
    
            $validator = Validator::make($request->all(), [
                'email'         => ['required', 'email'],
                'password'      => ['required', 'string'],
                'device_name'   => ['nullable', 'string'],
                'redirect_url'  => ['nullable', 'url', function ($attr, $value, $fail) use ($isAllowed) {
                    if (!$isAllowed($value)) {
                        $fail('URL is not allowed.');
                    }
                }],
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 200);
            }
    
            $data = $validator->validated();
    
            if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password',
                ], 200);
            }
    
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $device = $data['device_name'] ?? 'mobile';
            $user->user_type = '';
            $userType = $this->getUserTypeByReports($user);
            // optional: revoke previous token for this device
            $user->tokens()->where('name', $device)->delete();
    
            $token = $user->createToken($device)->plainTextToken;
    
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user'         => $user,
                     'user_type'  => $userType,   
                    'token'        => $token,
                    'token_type'   => 'Bearer',
                    'redirect_url' => $data['redirect_url'] ?? null, // echo back if provided
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('API login error', ['msg' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while logging in.',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email'       => ['required', 'email'],
                'password'    => ['required', 'string'],
                'device_name' => ['nullable', 'string', 'max:200'],
                // NEW: optional FCM fields coming from the app on login
                'fcm_token'   => ['nullable', 'string'],
                'platform'    => ['nullable', 'in:android,ios,web'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 200);
            }
    
            $data = $validator->validated();
    
            if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password',
                ], 200);
            }
    
            /** @var \App\Models\User $user */
            $user   = Auth::user();
            $device = $data['device_name'] ?? 'mobile';
    
            // Optional: revoke previous token for this device
            $user->tokens()->where('name', $device)->delete();
            $token = $user->createToken($device)->plainTextToken;
    
            // === NEW: save FCM token if provided ===
            if (!empty($data['fcm_token'])) {
                $raw   = (string) $data['fcm_token'];
                $clean = $raw;   // strip spaces/newlines
    
                // simple sanity check: real FCM tokens are usually long
                if (strlen($clean) >= 100) {
                    $exists = DB::table('user_device_tokens')
                        ->where('user_id', $user->id)
                        ->where('fcm_token', $clean)
                        ->first();
    
                    if (!$exists) {
                        DB::table('user_device_tokens')->insert([
                            'user_id'     => $user->id,
                            'fcm_token'   => $clean,
                            'platform'    => $data['platform'] ?? null,
                            'device_name' => $device,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]);
                    } else {
                        DB::table('user_device_tokens')
                            ->where('id', $exists->id)
                            ->update([
                                'platform'    => $data['platform'] ?? $exists->platform,
                                'device_name' => $device,
                                'updated_at'  => now(),
                            ]);
                    }
                } else {
                    Log::warning('Skipped storing short/invalid FCM token on login', [
                        'len' => strlen($clean),
                        'start' => substr($clean, 0, 10),
                        'end'   => substr($clean, -10),
                    ]);
                }
            }
            // === /NEW ===
    
            $user->user_type = '';
            $userType = $this->getUserTypeByReports($user);
    
            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user'       => $user,
                    'user_type'  => $userType,
                    'token'      => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('API login error', ['msg' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while logging in.',
            ], 500);
        }
    }
    
    public function getUserTypeByReports(User $user): string
    {
        // Find the employee row for this user (by user_id; fallback to email)
        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->orWhere(function ($q) use ($user) {
                $q->whereNotNull('email')->where('email', $user->email);
            })
            ->first();
    
        if (!$employee) {
            return 'user';
        }
    
        $eid = strtolower(trim((string) $employee->employee_id));
        if ($eid === '') {
            return 'employee';
        }
    
        // If any employee has report_to equal to this employee's employee_id -> manager
        $managesSomeone = Employee::whereNotNull('report_to')
            ->whereRaw('LOWER(TRIM(report_to)) = ?', [$eid])
            ->limit(1)
            ->exists();
    
        return $managesSomeone ? 'manager' : 'employee';
    }
    
    // GET /api/auth/me  (auth:sanctum)
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 200);
            }
    
            $imageUrl = $user->image
                ? asset('images/profile/'.$user->image)   // same path as your upload
                : null;
    
            return response()->json([
                'success' => true,
                'rafay'=>true,
                'data'    => array_merge($user->toArray(), [
                    'image_url' => $imageUrl,
                ]),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('API me error', ['msg' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
            ], 500);
        }
    }

    // POST /api/auth/logout  (auth:sanctum)
    public function logout(Request $request)
{
    try {
        $user = $request->user();
        
        // Delete the user's current access token
        $token = $user?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        // --- New: Delete the FCM token for the current user and device ---
        // Get the current device name from the request
        $device = $request->input('device_name', ''); // Default to 'mobile' if not provided
        
        // Remove the FCM token associated with this user and device
        DB::table('user_device_tokens')
            ->where('user_id', $user->id)
            ->where('device_name', $device)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out and FCM token removed',
        ], 200);
    } catch (\Throwable $e) {
        Log::error('API logout error', ['msg' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong while logging out.',
        ], 500);
    }
}


    // POST /api/auth/send-otp
    public function sendOtp(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'email' => ['required', 'email', 'exists:users,email'],
    ], ['email.exists' => 'We could not find a user with this email.']);

    if ($validator->fails()) {
        return response()->json([
            'status'  => false,
            'message' => 'Validation failed',
            'errors'  => $validator->errors(),
            'otp_sent' => false,
        ], 200);
    }

    $email = strtolower($request->input('email'));

    $recentCount = DB::table('password_otps')
        ->where('email', $email)
        ->where('created_at', '>=', now()->subHour())
        ->count();

    if ($recentCount >= 5) {
        return response()->json([
            'status'  => false,
            'message' => 'Too many OTP requests. Please try again later.',
            'otp_sent' => false,
        ], 429);
    }

    $otp = (string) random_int(100000, 999999);
    $otpHash = Hash::make($otp);

    DB::table('password_otps')->insert([
        'email'      => $email,
        'otp_hash'   => $otpHash,
        'expires_at' => now()->addMinutes(10),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Send email
    Mail::to($email)->send(new OtpMail($otp));

    return response()->json([
        'status'   => true,
        'message'  => 'OTP sent to your email (valid for 10 minutes).',
        'otp_sent' => true,
    ], 200);
}

    // POST /api/auth/verify-otp
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email', 'exists:users,email'],
            'otp'   => ['required', 'digits:6'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 200);
        }

        $email = strtolower($request->input('email'));
        $otp   = $request->input('otp');

        $row = DB::table('password_otps')
            ->where('email', $email)
            ->orderByDesc('id')
            ->first();

        if (!$row) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP not found. Please request a new one.',
            ], 200);
        }

        // expired?
        if (Carbon::parse($row->expires_at)->isPast()) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP expired. Please request a new one.',
            ], 200);
        }

        // too many attempts?
        if (($row->attempts ?? 0) >= 5) {
            return response()->json([
                'status'  => false,
                'message' => 'Too many incorrect attempts. Please request a new OTP.',
            ], 200);
        }

        // verify
        $isValid = Hash::check($otp, $row->otp_hash);

        // increment attempts
        DB::table('password_otps')->where('id', $row->id)->update([
            'attempts'  => ($row->attempts ?? 0) + 1,
            'updated_at' => now(),
        ]);

        if (!$isValid) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid OTP.',
            ], 200);
        }

        // success: clear all OTPs for this email
        DB::table('password_otps')->where('email', $email)->delete();

        // issue a short-scope Sanctum token (ability: password-reset)
        /** @var \App\Models\User $user */
        $user = User::where('email', $email)->first();
        $token = $user->createToken('pwd-reset', ['password-reset'])->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'OTP verified. Use the token to reset your password.',
            'token'   => $token,          // Bearer {token}
        ], 200);
    }

    // POST /api/auth/reset-password  (auth:sanctum)
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'], // expects new_password + new_password_confirmation
        ], [
            'new_password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 200);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthenticated.',
            ], 200);
        }

        // ensure this token has the special ability
        $token = $user->currentAccessToken();
        if (!$token || !$token->can('password-reset')) {
            return response()->json([
                'status'  => false,
                'message' => 'This token is not authorized to reset password.',
            ], 200);
        }

        // update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        // revoke this reset token (and optionally all others)
        $token->delete();
        // optional hardening: revoke all other tokens too
        // $user->tokens()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Password has been reset successfully.',
        ], 200);
    }

    public function changePassword(Request $request)
    {
    try {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 200);
        }

        // Validate inputs (confirm_password must match new_password)
        $validator = Validator::make($request->all(), [
            'old_password'     => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:new_password'],
        ], [
            'confirm_password.same' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 200);
        }

        // Verify old password
        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Old password is incorrect.',
            ], 200);
        }

        // Prevent reusing the same password
        if (Hash::check($request->input('new_password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password must be different from the old password.',
            ], 200);
        }

        // Update password
        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        // (Optional) Revoke other tokens, keep the current one
        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
        ], 200);

    } catch (\Throwable $e) {
        Log::error('API change-password error', [
            'user_id' => $request->user()?->id,
            'error' => $e->getMessage(),
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
        ], 500);
    }
}

    // GET /api/auth/qr-login?email=&token=&device_name=qr
   public function qrLogin(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'email'       => ['required', 'email'],
            'token'       => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
            // NEW: optional FCM fields coming from the app on login
            'fcm_token'   => ['nullable', 'string'],
            'platform'    => ['nullable', 'in:android,ios,web'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 200);
        }

        $email = strtolower($request->input('email'));
        $token = $request->input('token');

        // decrypt "userId|expiresAt"
        try {
            $plain = decrypt($token);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token.',
            ], 200);
        }

        [$userId, $expiresAt] = array_pad(explode('|', $plain, 2), 2, null);
        if (!$userId || !$expiresAt) {
            return response()->json([
                'success' => false,
                'message' => 'Malformed token.',
            ], 200);
        }
        if (\Carbon\Carbon::parse($expiresAt)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'QR token expired.',
            ], 200);
        }

        /** @var \App\Models\User|null $user */
        $user = User::where('id', $userId)->where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found for provided email/token.',
            ], 200);
        }

        $device = $request->input('device_name', 'qr');
        $user->tokens()->where('name', $device)->delete();
        $authToken = $user->createToken($device)->plainTextToken;

        // === NEW: save FCM token if provided ===
        $data = $validator->validated();
        if (!empty($data['fcm_token'])) {
            $raw   = (string) $data['fcm_token'];
            $clean = $raw;   // strip spaces/newlines

            // simple sanity check: real FCM tokens are usually long
            if (strlen($clean) >= 100) {
                $exists = DB::table('user_device_tokens')
                    ->where('user_id', $user->id)
                    ->where('fcm_token', $clean)
                    ->first();

                if (!$exists) {
                    DB::table('user_device_tokens')->insert([
                        'user_id'     => $user->id,
                        'fcm_token'   => $clean,
                        'platform'    => $data['platform'] ?? null,
                        'device_name' => $device,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                } else {
                    DB::table('user_device_tokens')
                        ->where('id', $exists->id)
                        ->update([
                            'platform'    => $data['platform'] ?? $exists->platform,
                            'device_name' => $device,
                            'updated_at'  => now(),
                        ]);
                }
            } else {
                Log::warning('Skipped storing short/invalid FCM token on login', [
                    'len' => strlen($clean),
                    'start' => substr($clean, 0, 10),
                    'end'   => substr($clean, -10),
                ]);
            }
        }
        // === /NEW ===

        $userType = $this->getUserTypeByReports($user);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user'       => $user,
                'user_type'  => $userType,
                'token'      => $authToken,
                'token_type' => 'Bearer',
            ],
        ], 200);

    } catch (\Throwable $e) {
        \Log::error('API qr-login error', ['msg' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong.',
        ], 500);
    }
}


}
