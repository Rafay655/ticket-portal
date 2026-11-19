<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;


class NotificationController extends Controller
{
    
    public function index(Request $request)
{
    $user = $request->user();
    
    $status   = $request->query('status', 'all');          // all|unread|read
    $perPage  = (int) $request->query('per_page', 20);
    $perPage  = $perPage > 100 ? 100 : max(1, $perPage);   // sane limits

    $query = $user->notifications()->orderByDesc('created_at');
    if ($status === 'unread')   $query->whereNull('read_at');
    if ($status === 'read')     $query->whereNotNull('read_at');

    $paginator = $query->paginate($perPage);

    $items = $paginator->getCollection()->map(function (DatabaseNotification $n) {
        $notificationData = $this->transform($n); // Transform the notification

        // Extract ticket_id from action_url
        $actionUrl = isset($notificationData['data']['action_url']) ? $notificationData['data']['action_url'] : null;
        $ticketId = null;

        if ($actionUrl) {
            $parsedUrl = parse_url($actionUrl); // Parse the URL
            $path = $parsedUrl['path'] ?? '';  // Extract the path from the URL
            $pathParts = explode('/', trim($path, '/')); // Split the path by '/'
            // If the last part is a valid ticket ID, assign it
            $ticketId = end($pathParts);
        }

        // Adding the ticket data by fetching the details
        $ticketData = $this->getTicketData($ticketId); // Call function to get ticket details

        // Add ticket data to notification response
        $notificationData['ticket_data'] = $ticketData;

        $a = 'App\Notifications\CommentAddedNotification';
        $b = $n->type ?? null;
        $notificationData['is_reply'] = $a == $b;

        return $notificationData;
    });

    return response()->json([
        'success' => true,
        'data' => [
            'items' => $items->values(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ],
    ], 200);
}

// Helper function to get ticket details
public function getTicketData($ticketId)
{
    $ticket = Ticket::with([
        'tickettype:id,name',
        'ticketSubType:id,name,priority',
        'requestedby:id,name',
        'assignto:id,name',
        'feedback',
    ])->find($ticketId);

    if (!$ticket) {
        return null; // If ticket not found, return null
    }

    // Build attachment URL (same logic as assigned())
    $name = $ticket->ticket_attachment;
    $attachmentUrl = $name
        ? (Str::startsWith($name, ['http://', 'https://', '//'])
            ? $name
            : asset('ticket_attachment/files/' . ltrim($name, '/')))
        : null;
    $ticket['attachment_url'] =  $attachmentUrl ;
    // Derived fields (add as required)
   

    // Response shape for ticket details
    

    return $ticket;
}


    public function summary(Request $request)
    {
        $user   = $request->user();
        $total  = $user->notifications()->count();
        $unread = $user->unreadNotifications()->count();
        $read   = $total - $unread;

        return response()->json([
            'success' => true,
            'data' => [
                'total'  => $total,
                'unread' => $unread,
                'read'   => $read,
            ],
        ], 200);
    }

    // POST /api/notifications/{id}/read
    public function markAsRead(Request $request, string $id)
    {
        $n = $request->user()->notifications()->where('id', $id)->firstOrFail();
        if (is_null($n->read_at)) $n->markAsRead();

        return response()->json(['success' => true, 'message' => 'Marked as read.'], 200);
    }

    // POST /api/notifications/{id}/unread
    public function markAsUnread(Request $request, string $id)
    {
        $n = $request->user()->notifications()->where('id', $id)->firstOrFail();
        if (!is_null($n->read_at)) {
            $n->read_at = null;
            $n->save();
        }
        return response()->json(['success' => true, 'message' => 'Marked as unread.'], 200);
    }

    // POST /api/notifications/mark-all-read
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true, 'message' => 'All marked as read.'], 200);
    }

    // ---------- Helpers ----------

    private function transform(DatabaseNotification $n): array
    {
        // Your blade used 'encrypted_data' => decrypt it if present
        $payload = $n->data ?? [];
        if (isset($payload['encrypted_data'])) {
            try {
                $payload = json_decode(Crypt::decryptString($payload['encrypted_data']), true) ?: [];
            } catch (\Throwable $e) {
                $payload = []; // fallback if corrupt
            }
        }

        // Pull actor user (if provided)
        $actor   = null;
        $actorId = $payload['user_id'] ?? $n->data['user_id'] ?? null;
        if ($actorId) {
            $actor = User::find($actorId);
        }

        $actorName = $actor?->name ?? 'Unknown User';
        $actorImg  = null;
        if ($actor && $actor->image) {
            // Legacy public/images/profile
            $path = public_path('images/profile/'.$actor->image);
            if (is_file($path)) {
                $actorImg = url('images/profile/'.$actor->image);
            }
        }

        return [
            'id'             => $n->id,
            'read'           => !is_null($n->read_at),
            'title'          => $payload['title']   ?? null,           // if you store it
            'message'        => $payload['message'] ?? null,
            'actor'          => [
                'id'   => $actor?->id,
                'name' => $actorName,
                'avatar_url' => $actorImg,
            ],
            'data'           => $payload,                               // raw (safe) payload
            'created_at'     => $n->created_at?->toIso8601String(),
            'read_at'        => $n->read_at?->toIso8601String(),
            'created_human'  => $n->created_at?->diffForHumans(),
        ];
    }
    
        /* ==========================
     * FCM — Minimal helpers (HTTP v1)
     * ========================== */

    private function fcmProjectId(): string
    {
        return 'flexi-desk';
    }

 // 100% hardcoded – ignores env/config

private function fcmCredentialsPath(): string
{
    // Absolute path to the JSON on your server
    $abs = '/home/hrpsp/testticket.myflexihr.com/storage/app/firebase/flexi-desk-firebase.json';

    if (!file_exists($abs)) {
        \Log::error('Firebase creds path does not exist', ['resolved' => $abs]);
        throw new \RuntimeException('Firebase credentials file not found: '.$abs);
    }
    if (is_dir($abs)) {
        \Log::error('Firebase creds path is a directory', ['resolved' => $abs]);
        throw new \RuntimeException('Firebase credentials path points to a directory: '.$abs);
    }
    if (!is_readable($abs)) {
        \Log::error('Firebase creds file not readable', ['resolved' => $abs]);
        throw new \RuntimeException('Firebase credentials file not readable: '.$abs);
    }
    return $abs;
}



    private function base64Url(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private function fcmAccessToken(): string
    {
        // Build a JWT & exchange for OAuth2 access token (valid for ~1h)
        $key = json_decode(file_get_contents($this->fcmCredentialsPath()), true);
        $clientEmail = $key['client_email'] ?? null;
        $privateKey  = $key['private_key']  ?? null;

        if (!$clientEmail || !$privateKey) {
            throw new \RuntimeException('Invalid Firebase service account JSON');
        }

        $now = time();
        $header  = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims  = $this->base64Url(json_encode([
            'iss'   => $clientEmail,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        openssl_sign("$header.$claims", $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = "$header.$claims.".$this->base64Url($signature);

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $code >= 400) {
            Log::error('FCM OAuth error', ['code' => $code, 'err' => $err, 'resp' => $response]);
            throw new \RuntimeException('Failed to obtain FCM access token.');
        }

        $json = json_decode($response, true);
        return $json['access_token'] ?? '';
    }

    /**
     * Send a push to a single token.
     * Adds both "notification" (for OS banner) and "data" (for your app logic).
     */
     private function assertSameProject(): void
{
    $json = json_decode(file_get_contents($this->fcmCredentialsPath()), true);
    $urlProject  = $this->fcmProjectId();
    $jsonProject = $json['project_id'] ?? null;

    if (!$jsonProject || $urlProject !== $jsonProject) {
        throw new \RuntimeException("FCM project mismatch: url='{$urlProject}' json='{$jsonProject}'");
    }
}

    private function fcmSendToToken(string $token, string $title, string $body, ?string $imageUrl = null, array $data = []): array
    {
         $this->assertSameProject(); 
        $payload = [
            'message' => array_filter([
                'token'        => $token,
                'notification' => array_filter([
                    'title' => $title,
                    'body'  => $body,
                    'image' => $imageUrl, // iOS/Web/Android (HTTP/HTTPS)
                ]),
                'android' => $imageUrl ? [
                    'notification' => ['image' => $imageUrl],
                ] : null,
                'data' => array_merge([
                    'title'              => $title,
                    'body'               => $body,
                    'notificationLayout' => 'BigText',
                    'bigPicture'         => $imageUrl ?? '',
                ], $data),
            ]),
        ];

        $ch = curl_init('https://fcm.googleapis.com/v1/projects/'.$this->fcmProjectId().'/messages:send');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer '.$this->fcmAccessToken(),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $err      = curl_error($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            Log::error('FCM CURL error', compact('errno','err'));
            return ['ok' => false, 'error' => $err];
        }

        $json = json_decode($response, true);
        if ($code >= 400) {
            Log::error('FCM HTTP error', ['status' => $code, 'resp' => $json ?? $response]);
            return ['ok' => false, 'status' => $code, 'resp' => $json ?? $response];
        }

        return ['ok' => true, 'resp' => $json];
    }

        // POST /api/notifications/register-token   { token, email? }
    public function registerFcmToken(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email', // if you want to register by email
        ]);
        
        // Resolve employee_id: prefer email param, fallback to auth user's email
        $email = $data['email'] ;
        if (!$email) {
            return response()->json(['success' => false, 'message' => 'Email required'], 200);
        }

        $employee = Employee::where('email', $email)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 200);
        }

        // Upsert latest token (keep your schema as-is)
        $exists = DB::table('notification_fcm_token')
            ->where('employee_id', $employee->id)
            ->where('fcm_token', $data['token'])
            ->first();

        if (!$exists) {
            DB::table('notification_fcm_token')->insert([
                'employee_id' => $employee->id,
                'fcm_token'   => $data['token'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            DB::table('notification_fcm_token')
                ->where('id', $exists->id)
                ->update(['updated_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Token registered.']);
    }


    // DELETE /api/notifications/unregister-token   { token, email? }
    public function unregisterFcmToken(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'email' => 'nullable|email',
        ]);

        $email = $data['email'] ?? $request->user()?->email;
        if (!$email) {
            return response()->json(['success' => false, 'message' => 'Email required'], 422);
        }

        $employee = DB::table('employees')->where('email', $email)->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        DB::table('notification_fcm_token')
            ->where('employee_id', $employee->id)
            ->where('fcm_token', $data['token'])
            ->delete();

        return response()->json(['success' => true, 'message' => 'Token removed.']);
    }


    // POST /api/notifications/push-by-email
    // { email, title, body, image_url?, extra?:{} }
    public function pushByEmail(Request $request)
    {
        $payload = $request->validate([
            'email'     => 'required|email',
            'title'     => 'required|string|max:120',
            'body'      => 'required|string|max:500',
            'image_url' => 'nullable|url',
            'extra'     => 'array',
        ]);

        $employee = DB::table('employees')->where('email', $payload['email'])->first();
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $tokenRow = DB::table('notification_fcm_token')
            ->where('employee_id', $employee->id)
            ->orderByDesc('id')
            ->first();

        if (!$tokenRow || empty($tokenRow->fcm_token)) {
            return response()->json(['success' => false, 'message' => 'No FCM token for this employee'], 404);
        }

        $resp = $this->fcmSendToToken(
            token:    $tokenRow->fcm_token,
            title:    $payload['title'],
            body:     $payload['body'],
            imageUrl: $payload['image_url'] ?? null,
            data:     $payload['extra'] ?? []
        );

        return response()->json(['success' => $resp['ok'], 'result' => $resp], $resp['ok'] ? 200 : 500);
    }



}
