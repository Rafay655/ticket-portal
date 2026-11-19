<?php

use App\Http\Controllers\Api\ChartApiController;
use App\Http\Controllers\Api\DataStatsApiController;
use App\Http\Controllers\Api\FirePropertyDamageApiController;
use App\Http\Controllers\Api\HazardApiController;
use App\Http\Controllers\Api\IEAuditClauseApiController;
use App\Http\Controllers\Api\IncidentApiController;
use App\Http\Controllers\Api\IncidentAsssignApiController;
use App\Http\Controllers\Api\InjuryApiController;
use App\Http\Controllers\Api\MetaDataApiController;
use App\Http\Controllers\Api\NearMissApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PermitToWorkApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\UnsafeBehaviorApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TicketApiController;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TicketTypeController;
use App\Notifications\DummyFcm;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use App\Services\FcmClient;


Route::post('login', [UserApiController::class, 'authUserLogin']);
Route::post('register', [UserApiController::class, 'registerUser']);
Route::post('reset-password/sendlink', [UserApiController::class, 'sendResetLink']);

Route::get('/test-fcm', function (FcmClient $fcm) {
    $token = 'cliOW9F5SVuBMFn5zvEnR6:APA91bHgzpRpX6HqGuqVGk51P572-2n7uJjuYZNeyNvad_diOCca8NnjA-qU1y80GCpYiKFsk9YIrve_rQiUiidz2jXC-7xMObeEjqVxfGeCSoXkZ1soSOI';
    $fcm->sendToToken($token, 'Firebase Test', 'This is a test notification from Laravel 🚀');
    return 'Notification sent!';
});

Route::prefix('auth')->group(function () {
    Route::post('/firstlogin',            [AuthController::class, 'firstlogin']);
    Route::post('/login',            [AuthController::class, 'login']);
   
    Route::get('/qr-login', [AuthController::class, 'qrLogin']); 
    Route::post('/send-otp',         [AuthController::class, 'sendOtp']);      // Params: email
    Route::post('/verify-otp',       [AuthController::class, 'verifyOtp']);    // Params: email, otp
    Route::post('/reset-password',   [AuthController::class, 'resetPassword']) // Params: new_password, confirm_password
        ->middleware('auth:sanctum'); // must use the token from verify-otp
        
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',            [AuthController::class, 'me']);
        Route::post('/logout',       [AuthController::class, 'logout']);
        Route::post('/me/change-password', [AuthController::class, 'changePassword'])->name('me.change-password');
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/ticket-types', [TicketTypeController::class, 'index']);
    Route::get('/ticket-types/{ticketType}/sub-types', [TicketTypeController::class, 'subTypes']);
});

Route::middleware('auth:sanctum')->prefix('tickets')->group(function () {
    Route::get('/my',       [TicketApiController::class, 'my']);       // GET /api/tickets/my
    Route::get('/assigned', [TicketApiController::class, 'assigned']); // GET /api/tickets/assigned
    Route::post('/', [TicketApiController::class, 'store']); 
    Route::get('/ticket/{id}', [TicketApiController::class, 'showDetail']);
    Route::get('/{ticket}/comments', [TicketApiController::class, 'getComments']);
    Route::get('/{ticket}/reassign/candidates', [TicketApiController::class, 'candidates']);
    Route::post('/{ticket}/reassign',            [TicketApiController::class, 'reassign']);
    Route::post('/{ticket}/update', [TicketApiController::class, 'apiUpdateLikeStore']);
    Route::post('/{id}/reply', [TicketApiController::class, 'apiReply']);
    Route::post('/{id}/close', [TicketApiController::class, 'apiClose']);
    Route::post('/{id}/feedback', [TicketApiController::class, 'apiFeedbackAdd']);
    Route::get('/user/stats', [TicketApiController::class, 'apiAssignedDashboard']);
    Route::get('/team/stats', [TicketApiController::class, 'apiAssigneeDashboard']);
    Route::get('/{id}/requester-card', [\App\Http\Controllers\Api\TicketApiController::class, 'requesterCard']);
    Route::get('/{id}/assignee-card',  [\App\Http\Controllers\Api\TicketApiController::class, 'assigneeCard']);
   
});

Route::middleware('auth:sanctum')->group(function () {
    // existing notification routes...

    // FCM helpers
    Route::post('/notifications/register-token', [NotificationController::class, 'registerFcmToken']);
    Route::delete('/notifications/unregister-token', [NotificationController::class, 'unregisterFcmToken']);
    Route::post('/notifications/push-by-email', [NotificationController::class, 'pushByEmail']);
});


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/summary', [NotificationController::class, 'summary']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/{id}/unread', [NotificationController::class, 'markAsUnread']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});

Route::middleware(['auth:sanctum','throttle:10,1'])->group(function () {
    Route::match(['patch','post'], '/me/profile', [MeController::class, 'update']);
    Route::get('/me', [MeController::class, 'show'])->name('me.show');
    Route::post('/profile/image', [MeController::class, 'updateImage']);
    Route::get('/me/team', [MeController::class, 'myTeam']);           // list + search
   
});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/user/{user_id}/update', [UserApiController::class, 'update']);
    Route::get('/user', [UserApiController::class, 'show']);
    Route::get('/users', [UserApiController::class, 'index']);
    Route::get('/user/roles', [UserApiController::class, 'showRoles']);
    Route::post('/user/change_password', [UserApiController::class, 'changePassword']);
    Route::get('/meta-data', [MetaDataApiController::class, 'index']);


    // hazards realted apis
    Route::get('/hazards', [HazardApiController::class, 'index']);
    Route::post('/hazards/create', [HazardApiController::class, 'store']);
    Route::get('/hazards/{hazard_id}/show', [HazardApiController::class, 'show']);
    Route::post('/hazards/{hazard_id}/update', [HazardApiController::class, 'update']);
    Route::post('/hazards/{hazard_id}/assign', [HazardApiController::class, 'assign']);
    Route::delete('/hazards/{hazard_id}/delete', [HazardApiController::class, 'destroy']);

    // unsafe behavior related apis
    Route::get('/unsafe_behaviors', [UnsafeBehaviorApiController::class, 'index']);
    Route::post('/unsafe_behaviors/create', [UnsafeBehaviorApiController::class, 'store']);
    Route::get('/unsafe_behaviors/{unsafe_behavior_id}/show', [UnsafeBehaviorApiController::class, 'show']);
    Route::post('/unsafe_behaviors/{unsafe_behavior_id}/update', [UnsafeBehaviorApiController::class, 'update']);
    Route::post('/unsafe_behaviors/{unsafe_behavior_id}/assign', [UnsafeBehaviorApiController::class, 'assign']);
    Route::delete('/unsafe_behaviors/{unsafe_behavior_id}/delete', [UnsafeBehaviorApiController::class, 'destroy']);


    // near miss apis
    Route::get('/near_misses', [NearMissApiController::class, 'index']);
    Route::post('/near_misses/create', [NearMissApiController::class, 'store']);
    Route::get('/near_misses/{near_miss_id}/show', [NearMissApiController::class, 'show']);
    Route::post('/near_misses/{near_miss_id}/update', [NearMissApiController::class, 'update']);
    Route::post('/near_misses/{near_miss_id}/assign', [NearMissApiController::class, 'assign']);
    Route::delete('/near_misses/{near_miss_id}/delete', [NearMissApiController::class, 'destroy']);

    // fire and proeprty damages apis
    Route::get('/fpdamages', [FirePropertyDamageApiController::class, 'index']);
    Route::post('/fpdamages/create', [FirePropertyDamageApiController::class, 'store']);
    Route::get('/fpdamages/{fpdamage_id}/show', [FirePropertyDamageApiController::class, 'show']);
    Route::post('/fpdamages/{fpdamage_id}/update', [FirePropertyDamageApiController::class, 'update']);
    Route::post('/fpdamages/{fpdamage_id}/assign', [FirePropertyDamageApiController::class, 'assign']);
    Route::delete('/fpdamages/{fpdamage_id}/delete', [FirePropertyDamageApiController::class, 'destroy']);


    // injury apis
    Route::get('/injuries', [InjuryApiController::class, 'index']);
    Route::post('/injuries/create', [InjuryApiController::class, 'store']);
    Route::get('/injuries/{injury_id}/show', [InjuryApiController::class, 'show']);
    Route::post('/injuries/{injury_id}/update', [InjuryApiController::class, 'update']);
    Route::post('/injuries/{injury_id}/assign', [InjuryApiController::class, 'assign']);
    Route::delete('/injuries/{injury_id}/delete', [InjuryApiController::class, 'destroy']);


    // permission to work apis
    Route::get('/ptws', [PermitToWorkApiController::class, 'index']);
    Route::post('/ptws/create', [PermitToWorkApiController::class, 'store']);
    Route::get('/ptws/{ptw_id}/show', [PermitToWorkApiController::class, 'show']);
    Route::post('/ptws/{ptw_id}/update', [PermitToWorkApiController::class, 'update']);
    Route::delete('/ptws/{ptw_id}/delete', [PermitToWorkApiController::class, 'destroy']);


    // internal external audit clause  apis
    Route::get('/ie_audits', [IEAuditClauseApiController::class, 'index']);
    Route::post('/ie_audits/create', [IEAuditClauseApiController::class, 'store']);
    Route::get('/ie_audits/{ie_audit_id}/show', [IEAuditClauseApiController::class, 'show']);
    Route::post('/ie_audits/{ie_audit_id}/update', [IEAuditClauseApiController::class, 'update']);
    Route::delete('/ie_audits/{ie_audit_id}/delete', [IEAuditClauseApiController::class, 'destroy']);
    Route::get('/ie_audits/answers', [IEAuditClauseApiController::class, 'getAnswers']);
    Route::get('/ie_audits/questions', [IEAuditClauseApiController::class, 'getQuestions']);


    // charts and graphs
    Route::get('/line_chart', [ChartApiController::class, 'line_chart']);
    Route::get('/line_chart/all_timelines', [ChartApiController::class, 'indexAllTimeLines']);
    Route::get('/card_chart', [ChartApiController::class, 'card_chart']);
    Route::get('/card_chart/all_timelines', [ChartApiController::class, 'piChartAllTimeLines']);
    Route::get('/bar_charts', [ChartApiController::class, 'barCharts']);

    // stats
    Route::get('/statistics', [DataStatsApiController::class, 'index']);
    Route::get('/statistics/summary', [DataStatsApiController::class, 'summary']);


    Route::post('/ie_audits/answers/submit', [IEAuditClauseApiController::class, 'submitAnswer']);
    Route::post('/ie_audits/answers/{answer_id}/update', [IEAuditClauseApiController::class, 'updateAnswer']);



    Route::get('/incidents', [IncidentApiController::class, 'index']);
    Route::post('/incident_assign', [IncidentAsssignApiController::class, 'storeByIncidentName']);
    Route::post('/incident_assign/reject', [IncidentAsssignApiController::class, 'rejectIncidentByName']);
    Route::post('/incident_assign/{incident_assign_id}/update', [IncidentAsssignApiController::class, 'update']);
    Route::post('/reset-password', [UserApiController::class, 'resetPasword']);
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::post('notifications', [NotificationApiController::class, 'activitySeen']);


    Route::post('reports/generate', [ReportApiController::class, 'createReport']);
    Route::get('reports', [ReportApiController::class, 'index']);
    Route::delete('/reports/{report_id}/delete', [ReportApiController::class, 'destroy']);



});