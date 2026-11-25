<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BarChartController;
use App\Http\Controllers\CardChartController;
use App\Http\Controllers\CommonAttachementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ExcelUtilityController;
use App\Http\Controllers\FirePropertyDamageController;
use App\Http\Controllers\HazardController;
use App\Http\Controllers\IEAuditBulkResponseController;
use App\Http\Controllers\IncidentAssignController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\InjuryController;
use App\Http\Controllers\InternalExternalAuditAnswerAttachementController;
use App\Http\Controllers\InternalExternalAuditAnswerController;
use App\Http\Controllers\InternalExternalAuditClauseController;
use App\Http\Controllers\LineChartController;
use App\Http\Controllers\MetaDataController;
use App\Http\Controllers\NearMissController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PermitToWorkController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolesPermissionController;
use App\Http\Controllers\UnsafeBehaviorController;
use App\Http\Controllers\UploadsController;
use App\Http\Controllers\UserController;
///////////////////////////////////////////////////////////////////////////
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\TicketTypeController;
use App\Http\Controllers\TicketCategoryController;
use App\Http\Controllers\TicketSubTypeController;
use App\Http\Controllers\TicketSettingController;
use App\Http\Controllers\EmployeesController;
use App\Http\Controllers\TicketsController;
use App\Http\Controllers\BusinessHoursController;
use App\Http\Controllers\SlaPolicyController;
use Illuminate\Support\Facades\Redis;
use App\Http\Middleware\Premissions;


use Illuminate\Support\Facades\Route;



Route::get('/check-redis', function () {
    try {
        // Set a test key
        Redis::set('test_key', 'Redis is working!');
        
        // Retrieve the value of the test key
        $value = Redis::get('test_key');
        
        return response()->json([
            'status' => 'success',
            'message' => $value
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Redis is not working: ' . $e->getMessage()
        ]);
    }
});

Route::middleware('auth')->group(function () {
    
    
    
    Route::resource('roles', RolesPermissionController::class);

    Route::get('/admintickets', [App\Http\Controllers\AllTicketsController::class, 'index'])->name('ticketShow.all');
    Route::get('report', [ReportController::class, 'index'])->name('report.index');
    Route::get('users/profile', [UserController::class, 'profileCreate'])->name('users.profileCreate');
    Route::get('report/pdf1', [ReportController::class, 'viewPDF'])->name('report.pdf1');
    Route::get('report/excel', [ReportController::class, 'downloadExcel'])->name('report.excel');
    Route::get('tickets_view/{id}', [TicketsController::class, 'tickets_view'])->name('ticket.view');
    Route::get('tickets_assign', [TicketsController::class, 'tickets_assign'])->name('tickets.assign');  
    Route::get('tickets_reassign/{id}', [TicketsController::class, 'tickets_reassign'])->name('tickets.reassign');
    Route::get('ticket/attachment/download/{id}', [TicketsController::class, 'ticket_attachment_download'])->name('ticket.attachment.download');
    Route::get('comment/attachment/download/{id}', [TicketsController::class, 'comment_attachment_download'])->name('comment.attachment.download');
    Route::get('comment/attachment/view/{id}', [TicketsController::class, 'comment_attachment_view'])->name('comment.attachment.view');
    Route::get('/send-email', [EmailController::class, 'sendEmail']);
    Route::get('/user-separation', [UserController::class, 'userSeparationIndex'])->name('user.separation.index');
    Route::get('/employee-separation', [EmployeesController::class, 'employeeSeparationIndex'])->name('employee.separation.index');

    Route::put('users/profile', [UserController::class, 'profileStore'])->name('users.profileStore');
    Route::post('/users/reactivate/{user_id}', [UserController::class, 'reactivate'])->name('users.reactivate');
    Route::post('/employees/reactivate/{employee_id}', [EmployeesController::class, 'reactivate'])->name('employees.reactivate');
    Route::post('import', [UploadsController::class, 'import'])->name('import');
    Route::post('import-ticket', [UploadsController::class, 'importTicket'])->name('import-ticket');
    Route::post('import-employee', [UploadsController::class, 'importEmployee'])->name('import-employee');
    Route::post('tickets_assignto', [TicketsController::class, 'tickets_assignto'])->name('tickets.assignto');
    Route::post('feedback_add', [TicketsController::class, 'feedback_add'])->name('feedback.add');
    Route::post('comment/add', [TicketsController::class, 'comment_add'])->name('comment.add');
    
    Route::get('ticket/close/{id}', function (\Illuminate\Http\Request $request, $id) {
        return redirect()->route('ticket.view', ['id' => $id, 'return_to' => $request->query('return_to')]);
    })->whereNumber('id')->name('ticket.close.preview');
    Route::post('ticket/close/{id}',  [TicketsController::class, 'ticket_close'])->name('ticket.close');

    Route::resource('slapolicies', SlaPolicyController::class);
    Route::resource('uploads', UploadsController::class);
    Route::resource('businesshours', BusinessHoursController::class);
    Route::resource('designations', DesignationController::class);
    Route::resource('departments', DepartmentController::class);
    Route::resource('grades', GradeController::class);
    Route::resource('regions', RegionController::class);
    Route::resource('tickettypes', TicketTypeController::class);
    Route::resource('ticketcategories', TicketCategoryController::class)->except('show');
    Route::resource('ticketsubtypes', TicketSubTypeController::class);
    Route::resource('ticketsetting', TicketSettingController::class);
    Route::resource('pdfreports', ReportController::class);
    Route::resource('employees', EmployeesController::class);
    Route::resource('/tickets', TicketsController::class);
    Route::POST('tickets_store', [TicketsController::class,'store'])->name('tickets_store');
    Route::resource('users', UserController::class);
  Route::get('/employees-export', [EmployeesController::class, 'export'])->name('employees.export');
    Route::get('/readtoggle/{id}', [TicketsController::class, 'toggleRead'])->name('toggle.notification');
    Route::get('/notifications/mark-all-read', [TicketsController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
   
    
    Route::get('department_users', [UserController::class, 'departmentUsers'])->name('users.department_users');
    
    // about
    // about company
    // Route::post('notifications', [NotificationController::class, 'activitySeen'])->name('notifications.activitySeen');
    ////////////////////////////////////////////////////////////////////////////////////////
    Route::get('pdfreports/metadata', [ReportController::class, 'metadata'])->name('pdfreports.metadata');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('meta-data/excel/import', [ExcelUtilityController::class, 'importMetaData'])->name('meta-data.excel.import');
    Route::get('/card_chart/all_timelines', [CardChartController::class, 'prepareForPiChart'])->name('pi_chart');
    Route::get('/bar_chart/all_timelines', [BarChartController::class, 'prepareForBarChart'])->name('bar_chart');
    Route::get('/line_chart/all_timelines', [LineChartController::class, 'prepareForLineChart'])->name('line_chart');
    

    Route::get('users/profile/{user_id}', [UserController::class, 'profife_view'])->name('users.view');
});

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/ticket/escalation/level', [CronJobController::class, 'ticket_escalation_level'])->name('ticket.escalation.level');

require __DIR__ . '/auth.php';



