<?php

namespace App\Http\Controllers;

use App\Mail\TicketAssignedMail;
use App\Models\TicketSetting;
use App\Models\TicketType;
use App\Models\Employee;
use App\Models\TicketSubType;
use App\Models\User;
use App\Models\Ticket;
use App\Services\TicketCreationGuard;
use App\Services\TicketHierarchy;
use App\Models\TicketTimeline;
use App\Models\Comment;
use App\Models\TicketFeedback;
use App\Notifications\CommentAddedNotification;
use App\Notifications\FeedbackAdded;
use App\Notifications\ResignTicket;
use App\Notifications\ticketClose;
use App\Notifications\ticketCreation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Response;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;


class TicketsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request, $channel = 'web')
    {
        RolesPermissionController::can(['ticket.index']);

        $authUserId   = auth()->id();
        $perPageInput = \App\Support\ListingFilters::perPage($request);
        $search       = \App\Support\ListingFilters::search($request);
        $activeTab    = $request->input('tab', 'my-tickets');

        // Base relations
        $with = ['tickettype', 'ticketsubtype.category', 'ticketCategory', 'comments', 'requestedby', 'assignto'];

        // My Tickets query
        $queryMy = Ticket::where('requested_by', $authUserId)->with($with);

        // Team Tickets query
        $emp = Employee::where('user_id', $authUserId)->first();
        if ($emp) {
            $subIds = collect($this->getAllSubordinates($emp->employee_id))
                      ->pluck('user_id')
                      ->all();
            $queryTeam = Ticket::whereIn('requested_by', $subIds)
                               ->where('assigned_to', '!=', $authUserId)
                               ->with($with);
        } else {
            // no subordinates → empty
            $queryTeam = Ticket::whereRaw('0 = 1')->with($with);
        }

        // Private POC tickets are only visible to POC users in Team Tickets.
        if ((int) auth()->user()->poc_user !== 1) {
            $queryTeam->whereDoesntHave('tickettype', function ($query) {
                $query->where('name', 'HRPSP POC');
            });
        }

        \App\Support\ListingFilters::tickets($queryMy, $request);
        \App\Support\ListingFilters::tickets($queryTeam, $request);

        // Resolve per-page counts (numeric vs “all”)
        if ($perPageInput === 'all') {
            $perPageMy   = max(1, $queryMy->count());
            $perPageTeam = max(1, $queryTeam->count());
        } else {
            $perPageMy   = (int)$perPageInput;
            $perPageTeam = (int)$perPageInput;
        }

        // Paginate & append query params
        $data['tickets'] = $queryMy->orderBy('created_at', 'desc')->orderBy('id', 'desc')
            ->paginate($perPageMy)

            ->appends([
                'tab'     => 'my-tickets',
                'perpage' => $perPageInput,
                'search'  => $search,
            ]);

        $data['team'] = $queryTeam->orderBy('created_at', 'desc')->orderBy('id', 'desc')
            ->paginate($perPageTeam)

            ->appends([
                'tab'     => 'team-tickets',
                'perpage' => $perPageInput,
                'search'  => $search,
            ]);

        $data['perPage']   = $perPageInput;
        $data['activeTab'] = $activeTab;

        return view('tickets.index', $data);
    }




    public function getAllSubordinates($employeeId)
    {
        $allSubordinates = [];
        $queue = [$employeeId]; // Start with the given employee ID

        while (!empty($queue)) {
            $currentId = array_shift($queue); // Get the first element
            $subordinates = Employee::where('report_to', $currentId)->get();

            foreach ($subordinates as $subordinate) {
                $allSubordinates[] = $subordinate; // Add to the result list
                $queue[] = $subordinate->employee_id; // Add subordinate ID to the queue for further processing
            }
        }

        return $allSubordinates;
    }


    // Controller method to fetch subordinates
    public function fetchSubordinates()
    {
        $authUserId = auth()->user()->id;

        $subordinates = $this->getAllSubordinates($authUserId);

        return response()->json($subordinates);
    }




    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        RolesPermissionController::can(['ticket.create']);
        $data = [];
        $data['tickettypes'] = TicketType::get();
        $data['ticketsubtypes'] = TicketSubType::get();
        $data['hierarchyData'] = app(TicketHierarchy::class)->options();

        return view('tickets.create', $data);
    }


   public function store(Request $request, $channel = "web")
    {
        RolesPermissionController::can(['ticket.create']);
        $request->validate([
            'tickettype_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'ticket_category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            'ticketsubtype_id' => ['required', 'integer', 'exists:ticket_sub_types,id'],
        ]);
        $classification = app(TicketHierarchy::class)->resolve((int) $request->tickettype_id, $request->ticket_category_id, $request->ticketsubtype_id);
        $creationGuard = app(TicketCreationGuard::class);
        $creationGuard->assertCanCreate((int) auth()->id(), $request->input('ticketsubtype_id'));
        $input = array_merge($request->all(), $classification);

        if ($request->hasFile('ticket_attachment')) {
            $fileName = time() . '.' . $request->ticket_attachment->getClientOriginalExtension();
            $request->ticket_attachment->move(public_path('ticket_attachment/files'), $fileName);
            $input['ticket_attachment'] = $fileName;
        }

        $ticket_type = TicketType::find($input['tickettype_id']);

       if ($ticket_type->name === 'HRPSP POC') {
    // 1. Grab the current user’s region IDs (JSON → PHP array)
    $currentRegionIds = auth()->user()->region_ids ?? [];

    // 2. Find one “POC USER” whose employee.region_ids overlap
    $poc = User::whereHas('roles', function($q) {
                $q->where('name', 'POC USER');
            })
            ->whereHas('employee', function($q) use ($currentRegionIds) {
                if (count($currentRegionIds) > 0) {
                    // pull off the first so we can seed a "where"
                    $first = array_shift($currentRegionIds);
                    $q->whereJsonContains('region_ids', $first);

                    // now chain the rest as ORs
                    foreach ($currentRegionIds as $rid) {
                        $q->orWhereJsonContains('region_ids', $rid);
                    }
                } else {
                    // if you want to allow POCs with no regions set:
                    $q->whereNull('region_ids')
                      ->orWhereJsonLength('region_ids', 0);
                }
            })
            // optional: distribute load if you have more than one match
            ->inRandomOrder()
            ->first();

    if (!$poc) {
        return redirect()
            ->route('tickets.create')
            ->with('failed', 'No regional POC USER found. Ticket cannot be created!');
    }

    $input['assigned_to'] = $poc->id;
}
else {
            $employee = Employee::where('user_id', auth()->user()->id)->first();
            if (!$employee) {
                return redirect()
                    ->route('tickets.create')
                    ->with('failed', 'Employee record not found. Ticket cannot be created!');
            }

            // Check if the employee has a "report_to"
            if (!$employee->report_to) {
                return redirect()
                    ->route('tickets.create')
                    ->with('failed', 'You are not reporting to anyone. Ticket cannot be created!');
            }
            $report_to = Employee::where('employee_id', $employee->report_to)->first();

            $assigned = User::find($report_to->user_id);
            $input['assigned_to'] = $assigned->id;
        }

        $input['requested_by'] = auth()->user()->id;
        $input['status'] = "Open";
        $input['current_level'] = 1;
        $input['escalation_time_start'] = Carbon::now();
        $ticket = $creationGuard->create($input);

        $assignedUser = User::find($input['assigned_to']);
        $reportingUser = auth()->user()->id;

        $userData = [
            'id' => $reportingUser,
            'ticket_id' => $ticket->id, // Assuming you have the ticket ID available here
        ];
        $assignedUser->notify(new ticketCreation($userData));
        return redirect()->route('tickets.index')->with('success', 'Ticket Create Successfully!');
    }






    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        RolesPermissionController::can(['ticket.view']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        RolesPermissionController::can(['ticket.edit']);
        $data = [];
        $data['ticket'] = $ticket;
        $data['hierarchyData'] = app(TicketHierarchy::class)->options();
        $data['tickettypes'] = TicketType::get();
        $data['ticketsubtypes'] = TicketSubType::get();
        return view('tickets.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        RolesPermissionController::can(['ticket.edit']);
        $request->validate([
            'tickettype_id' => ['required', 'integer', 'exists:ticket_types,id'],
            'ticket_category_id' => ['required', 'integer', 'exists:ticket_categories,id'],
            'ticketsubtype_id' => ['required', 'integer', 'exists:ticket_sub_types,id'],
        ]);
        $input = array_merge($request->all(), app(TicketHierarchy::class)->resolve((int) $request->tickettype_id, $request->ticket_category_id, $request->ticketsubtype_id));

        if ($request->hasFile('ticket_attachment')) {
            $fileName = time() . '.' . $request->ticket_attachment->getClientOriginalExtension();
            $request->ticket_attachment->move(public_path('ticket_attachment/files'), $fileName);
            $input['ticket_attachment'] = $fileName;
        }

        $ticket_type = TicketType::find($input['tickettype_id']);

        if ($ticket_type->name == 'HRPSP POC') {

            $user = User::where([
                ['region_id', '=', auth()->user()->region_id],
                ['poc_user', '=', 1],
            ])->first();

            $input['assigned_to'] = 4;
        } else {
            $employee = Employee::where('user_id', auth()->user()->id)->first();
            $report_to = Employee::where('employee_id', $employee->report_to)->first();
            $assigned = User::find($report_to->user_id);
            $input['assigned_to'] = $assigned->id;
        }

        $input['status'] = "Open";

        $ticket->fill($input);
        $ticket->save();

        $tickettimeline = TicketTimeline::where('ticket_id', $ticket->id)->latest()->first();

        if ($tickettimeline->assign_to != $ticket->assigned_to) {
            $this->ticket_time_line([
                'ticket_id' => $ticket->id,
                'assign_to' => $input['assigned_to'],
                'assign_time' => date('Y-m-d H:i:s'),
                'assign_type' => "By Update",
            ]);
        }

        return redirect($this->ticketReturnUrl($request, 'tickets.index'))->with('success', 'Ticket Update Successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        RolesPermissionController::can(['ticket.delete']);
        $ticket->delete();
        return redirect()->route('tickets.index')->with('success', 'Ticket has been deleted');
    }

    // public function tickets_assign(Request $request)
    // {
    //     $perPage = $request->input('perpage', 10); // Default to 10 items per page if not specified
    //     $data = [];

    //     // Fetch tickets assigned to the logged-in user with pagination
    //     $data['tickets'] = Ticket::where('assigned_to', auth()->user()->id)
    // ->with(['tickettype', 'comments', 'requestedby'])
    // ->latest('created_at')    // orderBy('updated_at','desc')
    // ->paginate($perPage)
    // ->appends(['perpage' => $perPage]);


    //     $data['perPage'] = $perPage;

    //     return view('tickets.assign', $data);
    // }
    // app/Http/Controllers/TicketController.php

public function tickets_assign(Request $request)
{
    RolesPermissionController::can(['ticket.index']);

    $authUserId   = auth()->id();
    $perPageInput = \App\Support\ListingFilters::perPage($request);
    $search       = \App\Support\ListingFilters::search($request);

    $query = Ticket::where('assigned_to', $authUserId)
                   ->with(['tickettype', 'ticketsubtype.category', 'ticketCategory','comments','requestedby','assignto']);


   \App\Support\ListingFilters::tickets($query, $request);


    // “All” support
    if ($perPageInput === 'all') {
        $perPage = max(1, $query->count());
    } else {
        $perPage = (int)$perPageInput;
    }

    // Final paginate & append params
    $tickets = $query
        ->orderBy('created_at', 'desc')->orderBy('id', 'desc')
        ->paginate($perPage)
        ->appends([
            'perpage' => $perPageInput,
            'search'  => $search,
        ]);

    return view('tickets.assign', [
        'tickets'    => $tickets,
        'perPage'    => $perPageInput,
        'search'     => $search,
    ]);
}


    public function tickets_reassign($id)
    {
        $data = [];
        $ticket = Ticket::find($id);
        if (!$ticket) {
            return redirect()->back()->withErrors('Ticket not found.');
        }
        $prev = $ticket->prev_assigned;
        $employee = Employee::where('user_id', $ticket->assigned_to)->first();
        if (!$employee) {
            return redirect()->back()->withErrors('Assigned employee not found.');
        }
        Logger($prev);
        if ($prev) {
            $employee1 = Employee::where('user_id', $prev)->first();
        }
        if ($employee1) {
            $data['users'] = User::where('id', $employee1->user_id)->get();
        } else {

            $userIds = Employee::where('report_to', $employee->employee_id)
                ->whereNotIn('user_id', [$ticket->requested_by])
                ->pluck('user_id')->toArray();
            $data['users'] = User::whereIn('id', $userIds)->get();
        }

        $data['ticket'] = $ticket;
        // dd($userIds);
        return view('tickets.reassign', $data);
    }



    public function tickets_assignto(Request $request)
    {
        $ticket = Ticket::find($request->ticket_id);
        $ticket->assigned_to = $request->assign_to;
        $ticket->escalation_time_start = now();
         $ticket->current_level   = 1;
        // $ticket->current_level = $ticket->current_level - 1;
        $oppositeUser = User::find($ticket->assigned_to);

        $ticket->save();
        $this->ticket_time_line([
            'ticket_id' => $ticket->id,
            'assign_to' => $request->assign_to,
            'assign_time' => date('Y-m-d H:i:s'),
            'assign_type' => "By Reassign",
        ]);
        $userData = [
            'id' => $ticket->requested_by,
            'ticket_id' => $request->ticket_id,
        ];
        $oppositeUser->notify(new ResignTicket($userData));

        return redirect($this->ticketReturnUrl($request, 'tickets.assign'))->with('success', 'Ticket Reassign Successfully!');
    }



    public function feedback_add(Request $request)
    {
        $input = $request->all();
        $ticketID = $input['ticket_id'];
        $reviewerID = $input['reviewer'];
        $ticket = Ticket::find($ticketID);
        $oppositeUser = User::find($ticket->assigned_to);
        // dd($input);
        $userData = [
            'id' => $reviewerID,
            'ticket_id' => $ticketID,
        ];
        $comment = [
            'comment' => isset($input['review']) && !empty($input['review']) ? $input['review'] : null,
            'comment_attachment' => isset($input['rate']) && !empty($input['rate']) ? $input['rate'] : null,
        ];
        $oppositeUser->notify(new FeedbackAdded($userData, $comment));
        $ticket = Ticket::find($ticketID);
        if ($ticket && $ticket->status == 'Closed') {
            $ticket->status = 'Feedback';
            $ticket->save();
        }
        TicketFeedback::create($input);

        return redirect($this->ticketReturnUrl($request, 'tickets.index'))->with('success', 'Feedback added Successfully!');
    }


    public function tickets_view($id)
{
    try {
        // Check if the user has permission
        $ticket = Ticket::find($id);
         if (! auth()->user()->can('admindashboard.index')) {
        // 1) Ticket must exist
        if (! $ticket) {
            return redirect()->back()->with('error', 'Ticket not found');
        }

        // 2) Build the list of user_ids that report to you
        $me = Employee::where('user_id', auth()->id())->first();
        $subordinateUserIds = [];
        if ($me) {
            $subs = $this->getAllSubordinates($me->employee_id);
            $subordinateUserIds = collect($subs)->pluck('user_id')->toArray();
        }

        // 3) Deny unless you are the requester, the assignee, or a manager above the requester
        $you   = auth()->id();
        $owner = $ticket->requested_by;
        $assn  = $ticket->assigned_to;

        if (
            $you !== $owner
            && $you !== $assn
            && ! in_array($owner, $subordinateUserIds)
        ) {
            return redirect()->back()
                             ->with('error', 'You are not authorized to perform this action');
        }
    }

        // Ticket feedback exists check
        $ticket_feedback_exists = TicketFeedback::where('ticket_id', $ticket->id)->exists();
        $priority = $ticket->ticketSubType ? $ticket->ticketSubType->priority : null;

        $assignedUserName = User::find($ticket->assigned_to);
        $requested_byName = User::find($ticket->requested_by);

        // Check for null values and handle appropriately
        $departmentName = $requested_byName && $requested_byName->department
            ? $requested_byName->department->name
            : 'No Department';
        $designationName = $requested_byName && $requested_byName->designation
            ? $requested_byName->designation->name
            : 'No Designation';
        $regionName = $requested_byName && $requested_byName->region
            ? $requested_byName->region->name
            : 'No Region';
        $subregionName = $requested_byName && $requested_byName->subregion
            ? $requested_byName->subregion->name
            : 'No Subregion';
        $review_open = $ticket->feedback()->exists();

        // Prepare data for the view
        $data = [
            'ticket' => $ticket,
            'priority' => $priority,
            'assignedUserName' => $assignedUserName,
            'requested_byName' => $requested_byName,
            'departmentName' => $departmentName,
            'designationName' => $designationName,
            'regionName' => $regionName,
            'subregionName' => $subregionName,
            'ticket_feedback_exists' => $ticket_feedback_exists,
        ];

        return view('tickets.view', $data);

    } catch (\Exception $e) {
        // Log error if necessary (optional)
        // Log::error($e->getMessage());

        // Redirect back with error message
         return redirect()->back()->with('error', 'Internal Server Error');
    }
}



    public function ticket_attachment_download($id)
    {
        $ticket = Ticket::find($id);
        return Response::download(public_path('ticket_attachment/files/' . $ticket->ticket_attachment));
    }

    public function comment_attachment_download($id)
    {
        $comment = Comment::find($id);
        return Response::download(public_path('comment_attachment/files/' . $comment->comment_attachment));
    }
    public function comment_attachment_view($id)
    {
        $comment = Comment::find($id);

        if ($comment && $comment->comment_attachment) {
            $path = public_path('comment_attachment/files/' . $comment->comment_attachment);

            if (file_exists($path)) {
                $mimeType = mime_content_type($path);
                if (in_array($mimeType, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])) {
                    return response()->file($path);
                } else {
                    return redirect()->back()->with('error', 'This file type cannot be viewed in the browser.');
                }
            } else {
                return redirect()->back()->with('error', 'File not found.');
            }
        } else {
            return redirect()->back()->with('error', 'Comment or attachment not found.');
        }
    }

    public function comment_add(Request $request)
    {
        \App\Support\CommentContent::validate($request);
        $request->validate(['ticket_id' => ['required', 'integer', 'exists:tickets,id']]);
        $ticket = Ticket::findOrFail($request->input('ticket_id'));
        abort_unless(in_array((int) auth()->id(), [(int) $ticket->requested_by, (int) $ticket->assigned_to], true), 403);
        $input = $request->only('ticket_id', 'description');
        $input['description'] = trim((string) ($input['description'] ?? ''));
        $input['commenter'] = auth()->id();
        // dd($input);
        if ($request->hasFile('comment_attachment')) {
            $fileName = time() . '.' . $request->comment_attachment->getClientOriginalExtension();
            $request->comment_attachment->move(public_path('comment_attachment/files'), $fileName);
            $input['comment_attachment'] = $fileName;
        }
        if ($request->has('voice_note') && !empty($request->voice_note)) {
            $input['voice_note_path'] = \App\Support\CommentContent::saveAudio($request->voice_note);
        }

        Comment::create($input);
        $ticketId = $input['ticket_id'];
        $commentCount = Comment::where('ticket_id', $ticketId)->count();

        if ($commentCount == 1) { // If this is the first comment
            $ticket = Ticket::find($ticketId);
            if ($ticket && $ticket->status == 'Open') {
                $ticket->status = 'in_progress';
                $ticket->save();
            }
        }
        $ticket = Ticket::where('id', $ticketId)->first();
        $commenterId = $input['commenter']; // The ID of the commenter

        $comment = [
            'comment' => isset($input['description']) && !empty($input['description']) ? $input['description'] : null,
            'comment_attachment' => isset($input['comment_attachment']) && !empty($input['comment_attachment']) ? $input['comment_attachment'] : null,
            'voice_note_path' => $input['voice_note_path'] ?? null,
        ];

        if ($ticket->requested_by == $commenterId) {
            $oppositeUser = User::find($ticket->assigned_to);
        } elseif ($ticket->assigned_to == $commenterId) {
            $oppositeUser = User::find($ticket->requested_by);
        } else {
            return redirect()->back()->with('error', 'Commenter does not belong to this ticket.');
        }


        if ($oppositeUser) {
            $userData = [
                'id' => $commenterId,
                'ticket_id' => $ticket->id,
                'comment' => $comment
            ];
            $oppositeUser->notify(new CommentAddedNotification($userData));
        }


        return redirect($this->ticketReturnUrl($request))->with('success', 'Comment Added Successfully');
    }

    public function ticket_close(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        abort_unless((int) auth()->id() === (int) $ticket->assigned_to || auth()->user()->can('admindashboard.index'), 403);
        $request->merge(['description' => is_string($request->input('description')) ? trim($request->input('description')) : $request->input('description')]);
        $request->validate([
            'description' => ['required', 'string', 'max:10000'],
            'comment_attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,mp3,wav', 'max:5120'],
        ], ['description.required' => 'Please enter a resolution before closing this ticket.']);
        $input = ['ticket_id' => $ticket->id, 'commenter' => auth()->id(), 'description' => $request->input('description')];
        // Update the ticket status to Closed
        $ticket->status = "Closed";

        // Handle file attachment
        if ($request->hasFile('comment_attachment')) {
            $fileName = time() . '.' . $request->comment_attachment->getClientOriginalExtension();
            $request->comment_attachment->move(public_path('comment_attachment/files'), $fileName);
            $input['comment_attachment'] = $fileName;
        }
        // Save the comment
        Comment::create($input);

        $ticket->save();
        $assignedUser = User::find($ticket->requested_by);
        $userData = [
            'id' => $ticket->assigned_to,
            'ticket_id' => $ticket->id, // Assuming you have the ticket ID available here
        ];
        $comment = [
            'comment' => isset($input['description']) && !empty($input['description']) ? $input['description'] : null,
            'comment_attachment' => isset($input['comment_attachment']) && !empty($input['comment_attachment']) ? $input['comment_attachment'] : null,
        ];
        $assignedUser->notify(new ticketClose($userData, $comment));

        return redirect($this->ticketReturnUrl($request, 'tickets.index'))->with('success', 'Ticket closed and comment added successfully.');
    }

    private function ticketReturnUrl(Request $request, $fallback = 'tickets.index')
    {
        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && !preg_match('/[\x00-\x20\x7f]/', $returnTo)) {
            $base = explode('?', $returnTo, 2)[0];
            foreach (['tickets.index', 'tickets.assign', 'ticketShow.all'] as $route) {
                if ($base === route($route)) {
                    return $returnTo;
                }
            }
        }

        return route($fallback);
    }

    private function ticket_time_line($data)
    {
        TicketTimeline::create($data);
    }

    public function profileCreate()
    {
        return view('users.profile');
    }

    public function profileStore(Request $request)
    {
        return $this->update($request, auth()->user()->id, 'web');
    }

    public function countAllTickets()
    {
        $totalTickets = Ticket::count();
        return response()->json([
            'total_tickets' => $totalTickets,
        ]);
    }

    public function toggleRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);

        if ($notification) {
            if ($notification->read_at == null) {
                $notification->markAsRead();
            }

            $decryptedData = json_decode(Crypt::decryptString($notification->data['encrypted_data']), true);

            return redirect($decryptedData['action_url']);
        }

        return back();
    }

    public function markAllAsRead()
    {
        // Mark all unread notifications as read for the authenticated user
        auth()->user()->unreadNotifications->markAsRead();

        // Redirect back to the previous page
        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
