<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Region;
use App\Models\Ticket;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\TicketType;
use App\Models\TicketTimeline;
use App\Models\User;
use App\Notifications\ticketCreation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Notifications\CommentAddedNotification;
use App\Notifications\ticketClose;
use App\Notifications\FeedbackAdded;
use App\Models\TicketFeedback;
use DB;
use App\Notifications\ResignTicket;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TicketApiController extends Controller
{
    public function my(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
    
        $paginator = Ticket::with([
            'tickettype:id,name',
            'ticketSubType:id,name,priority',
            'requestedby:id,name',
            'assignto:id,name',
        ])
        ->withCount('comments')  
            ->where('requested_by', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    
        // Add attachment URL like in assigned()
        $items = $paginator->getCollection()->map(function ($t) {
            $name = $t->ticket_attachment;
            $t->attachment_url = $name
                ? (Str::startsWith($name, ['http://', 'https://', '//'])
                    ? $name
                    : asset('ticket_attachment/files/' . ltrim($name, '/')))
                : null;
            return $t;
        })->values();
    
        return response()->json([
            'status'  => true,
            'message' => 'My tickets fetched',
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }


    public function assigned(Request $request)
    {
        $perPage = (int) $request->query('per_page', 10);
    
        $paginator = Ticket::with([
            'tickettype:id,name',
            'ticketSubType:id,name,priority',
            'requestedby:id,name',
            'assignto:id,name',
        ])
        ->withCount('comments')  
            ->where('assigned_to', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    
        $items = $paginator->getCollection()->map(function ($t) {
            $name = $t->ticket_attachment;
            $t->attachment_url = $name
                ? (Str::startsWith($name, ['http://','https://','//'])
                    ? $name
                    : asset('ticket_attachment/files/'.ltrim($name, '/')))
                : null;
            return $t;
        })->values();
    
        return response()->json([
            'status'  => true,
            'message' => 'Assigned tickets fetched',
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        // ---- Validate ----
        $validator = Validator::make($request->all(), [
            'tickettype_id'     => ['required', 'exists:ticket_types,id'],
            'ticketsubtype_id'  => ['nullable', 'exists:ticket_sub_types,id'],
            'subject'           => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'ticket_attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $input = $validator->validated();

        // ---- Attachment upload (optional) ----
        $attachmentUrl = null;

        if ($request->hasFile('ticket_attachment')) {
            $dest = public_path('ticket_attachment/files');
            if (!File::exists($dest)) {
                File::makeDirectory($dest, 0775, true);
            }

            $fileName = time().'_'.Str::random(6).'.'.$request->file('ticket_attachment')->getClientOriginalExtension();
            $request->file('ticket_attachment')->move($dest, $fileName);

            $input['ticket_attachment'] = $fileName;
            $attachmentUrl = asset('ticket_attachment/files/'.$fileName);
        }

        // ---- Assignment logic ----
        $ticketType = TicketType::find($input['tickettype_id']);

        if ($ticketType && $ticketType->name === 'HRPSP POC') {
            $currentRegionIds = (array) ($request->user()->employee->region_ids ?? []);
            $currentRegionIds = array_values(array_filter($currentRegionIds, fn($v) => $v !== null && $v !== ''));

            $pocUser = User::whereHas('roles', fn($q) => $q->where('name', 'POC USER'))
                ->whereHas('employee', function ($q) use ($currentRegionIds) {
                    $q->where(function ($w) use ($currentRegionIds) {
                        if (!empty($currentRegionIds)) {
                            foreach ($currentRegionIds as $rid) {
                                $w->orWhereJsonContains('region_ids', (int) $rid)
                                  ->orWhereJsonContains('region_ids', (string) $rid);
                            }
                            foreach ($currentRegionIds as $rid) {
                                $w->orWhereRaw("FIND_IN_SET(?, COALESCE(region_ids,''))", [(string)$rid]);
                            }
                        }
                        $w->orWhereNull('region_ids');
                        try { $w->orWhereJsonLength('region_ids', 0); } catch (\Throwable $e) {}
                    });
                })
                ->first();

            if (!$pocUser) {
                return response()->json([
                    'status'  => false,
                    'message' => 'POC user not found for your region(s).',
                ], 422);
            }

            $input['assigned_to'] = $pocUser->id;
        } else {
            $employee = Employee::where('user_id', $request->user()->id)->first();
            if (!$employee) {
                return response()->json(['status' => false, 'message' => 'Employee record not found.'], 422);
            }
            if (!$employee->report_to) {
                return response()->json(['status' => false, 'message' => 'You are not reporting to anyone.'], 422);
            }

            $reportTo = Employee::where('employee_id', $employee->report_to)->first();
            if (!$reportTo) {
                return response()->json(['status' => false, 'message' => 'Reporting manager not found.'], 422);
            }

            $assigned = User::find($reportTo->user_id);
            if (!$assigned) {
                return response()->json(['status' => false, 'message' => 'Reporting user not found.'], 422);
            }

            $input['assigned_to'] = $assigned->id;
        }

        // ---- Common fields ----
        $input['requested_by']          = $request->user()->id;
        $input['status']                = 'Open';
        $input['current_level']         = 1;
        $input['escalation_time_start'] = Carbon::now();

        // ---- Create ticket ----
        $ticket = Ticket::create($input);

        // ---- Timeline ----
        TicketTimeline::create([
            'ticket_id'   => $ticket->id,
            'assign_to'   => $input['assigned_to'],
            'assign_time' => now(),
            'assign_type' => 'By Create',
        ]);

        // ---- Notify assignee ----
        if ($assignee = User::find($input['assigned_to'])) {
            $assignee->notify(new ticketCreation([
                'id'        => $request->user()->id,
                'ticket_id' => $ticket->id,
            ]));
        }

        // ---- Eager load + finalize URL ----
        $ticket->load([
            'tickettype:id,name',
            'ticketSubType:id,name,priority',
            'requestedby:id,name',
            'assignto:id,name',
        ]);

        $attachmentUrl = $attachmentUrl ?? ($ticket->ticket_attachment
            ? asset('ticket_attachment/files/'.$ticket->ticket_attachment) : null);

        return response()->json([
            'status'         => true,
            'message'        => 'Ticket created successfully!',
            'data'           => $ticket,
            'attachment_url' => $attachmentUrl,
        ], 201);
    }

    public function showDetail(Request $request, $id)
    {
        $ticket = Ticket::with([
            'tickettype:id,name',
            'ticketSubType:id,name,priority',
            'requestedby:id,name',
            'assignto:id,name',
            'feedback',
        ])->find($id);
    
        if (!$ticket) {
            return response()->json([
                'status'  => false,
                'message' => "Ticket not found for id {$id}",
            ], 404);
        }
    
        // Auth check: admin OR requester OR assignee
        $user    = $request->user();
        // $isAdmin = method_exists($user, 'can') && $user->can('admindashboard.index');
    
        // if (!$isAdmin && $user->id !== (int) $ticket->requested_by && $user->id !== (int) $ticket->assigned_to) {
        //     return response()->json([
        //         'status'  => false,
        //         'message' => 'You are not authorized to view this ticket.',
        //     ], 403);
        // }
    
        // Build attachment URL (same logic as assigned())
        $name = $ticket->ticket_attachment;
        $attachmentUrl = $name
            ? (Str::startsWith($name, ['http://','https://','//'])
                ? $name
                : asset('ticket_attachment/files/' . ltrim($name, '/')))
            : null;
    
        // Derived fields
        $priority        = optional($ticket->ticketSubType)->priority;
        $requestedUser   = $ticket->requestedby;
        $departmentName  = optional(optional($requestedUser)->department)->name ?? 'No Department';
        $designationName = optional(optional($requestedUser)->designation)->name ?? 'No Designation';
        $regionName      = optional(optional($requestedUser)->region)->name ?? 'No Region';
        $subregionName   = optional(optional($requestedUser)->subregion)->name ?? 'No Subregion';
    
        $ticket_feedback_exists = \App\Models\TicketFeedback::where('ticket_id', $ticket->id)->exists();
        $review_open = method_exists($ticket, 'feedback') ? $ticket->feedback()->exists() : false;
    
        // History
        $timelines = \App\Models\TicketTimeline::where('ticket_id', $ticket->id)
            ->orderBy('assign_time', 'asc')
            ->get(['assign_to', 'assign_time', 'assign_type', 'ticket_id']);
    
        $history = $timelines->map(function ($t) {
            $assignee = \App\Models\User::find($t->assign_to);
            return [
                'assign_to'      => $t->assign_to,
                'assign_to_name' => optional($assignee)->name,
                'assign_time'    => (string) $t->assign_time,
                'assign_type'    => $t->assign_type,
            ];
        })->values();
    
        // Response shape
        $response = [
            
                'id'                     => $ticket->id,
                'ticket_no'              => $ticket->id ?? null,
                'subject'                => optional($ticket->ticketSubType)->name ?? null,
                'description'            => $ticket->description ?? null,
                'tickettype'             => optional($ticket->tickettype)->name,
                'ticketsubtype'          => optional($ticket->ticketSubType)->name,
                'priority'               => $priority,
                'requested_by'           => optional($ticket->requestedby)->name,
                'department'             => $departmentName,
                'designation'            => $designationName,
                'region'                 => $regionName,
                'level'                  => $ticket->current_level ?? 1,
                'subregion'              => $subregionName,
                'ticket_feedback_exists' => $ticket_feedback_exists,
                'review_open'            => $review_open,
                'attachment_url'         => $attachmentUrl, // <- added here
                'created_at'             => (string) $ticket->created_at,
                'updated_at'             => (string) $ticket->updated_at,
           
            'assigned_to' => [
                'id'   => $ticket->assigned_to,
                'name' => optional($ticket->assignto)->name,
            ],
            'status'  => $ticket->status,
            'history' => $history,
        ];
    
        return response()->json([
            'status'  => true,
            'message' => 'Ticket detail fetched',
            'data'    => $response,
        ], 200);
    }

    public function apiUpdateLikeStore(Request $request, Ticket $ticket)
    {
        // RolesPermissionController::can(['ticket.edit']);

        // Validate minimum needed (expand as you like)
        $validator = Validator::make($request->all(), [
            'tickettype_id'     => 'required|integer|exists:ticket_types,id',
            'ticket_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $input = $request->all();

        // ---- File upload (same path & field name as store) ----
        if ($request->hasFile('ticket_attachment')) {
            $fileName = time() . '.' . $request->file('ticket_attachment')->getClientOriginalExtension();
            $request->file('ticket_attachment')->move(public_path('ticket_attachment/files'), $fileName);
            $input['ticket_attachment'] = $fileName;
        }

        // ---- Assignment logic (same as store) ----
        $ticket_type = TicketType::find($input['tickettype_id']);

        if ($ticket_type && $ticket_type->name === 'HRPSP POC') {
            $currentRegionIds = auth()->user()->employee->region_ids ?? [];

            $user = User::whereHas('roles', function ($q) {
                $q->where('name', 'POC USER');
            })
                ->whereHas('employee', function ($q) use ($currentRegionIds) {
                    $q->where(function ($q2) use ($currentRegionIds) {
                        foreach ((array) $currentRegionIds as $rid) {
                            $q2->orWhereJsonContains('region_ids', (string) $rid);
                        }
                        $q2->orWhereNull('region_ids')
                            ->orWhereJsonLength('region_ids', 0);
                    });
                })
                ->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No POC user found. Ticket cannot be updated!',
                ], 422);
            }

            $input['assigned_to'] = $user->id;
        } else {
            $employee = Employee::where('user_id', auth()->user()->id)->first();
            if (!$employee) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee record not found. Ticket cannot be updated!',
                ], 422);
            }

            if (!$employee->report_to) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not reporting to anyone. Ticket cannot be updated!',
                ], 422);
            }

            $report_to = Employee::where('employee_id', $employee->report_to)->first();
            $assigned  = $report_to ? User::find($report_to->user_id) : null;

            if (!$assigned) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assigned user not found. Ticket cannot be updated!',
                ], 422);
            }

            $input['assigned_to'] = $assigned->id;
        }

        // ---- Do NOT change requested_by on update ----
        unset($input['requested_by']);

        // ---- Reset status & escalation (same spirit as store) ----
        $input['status'] = 'Open';
        $input['current_level'] = $ticket->current_level ?? 1;
        $input['escalation_time_start'] = Carbon::now();

        // ---- Save & record timeline if assignee changed ----
        $previousAssigned = $ticket->assigned_to;
        $ticket->fill($input);
        $ticket->save();

        if ((int) $previousAssigned !== (int) $ticket->assigned_to) {
            $this->ticket_time_line([
                'ticket_id'   => $ticket->id,
                'assign_to'   => $ticket->assigned_to,
                'assign_time' => date('Y-m-d H:i:s'),
                'assign_type' => 'By Update',
            ]);
        }

        // ---- Optional: notify assignee like store() (ticketCreation) ----
        $assignedUser  = isset($input['assigned_to']) ? User::find($input['assigned_to']) : null;
        $reportingUser = auth()->user()->id;
        if ($assignedUser) {
            $userData = [
                'id'        => $reportingUser,
                'ticket_id' => $ticket->id,
            ];
            $assignedUser->notify(new ticketCreation($userData));
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket updated successfully.',
            'data'    => [
                'ticket_id'            => $ticket->id,
                'status'               => $ticket->status,
                'assigned_to'          => $ticket->assigned_to,
                'tickettype_id'        => $input['tickettype_id'],
                'ticket_attachment'    => $ticket->ticket_attachment ?? ($input['ticket_attachment'] ?? null),
                'current_level'        => $ticket->current_level,
                'escalation_time_start' => (string) $ticket->escalation_time_start,
            ],
        ], 200);
    }

    public function apiReply(Request $request, $id)
    {
        // Validate minimally (keep parity with web flow)
        $validator = Validator::make($request->all(), [
            'description'        => 'nullable|string',
            'comment_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,mp3,wav|max:5120',
            'voice_note'         => 'nullable|string', // data URI (base64)
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        $user = $request->user();

        // Only participants (requester or assignee) or privileged users may reply
        $canAdmin = $user->can('ticket.edit') || $user->can('admindashboard.index');
        if (!$canAdmin && !in_array($user->id, [$ticket->requested_by, $ticket->assigned_to])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $input = $request->all();
        $input['ticket_id'] = $ticket->id;
        $input['commenter'] = $user->id;

        // ---- Handle file attachment (same path as web) ----
        if ($request->hasFile('comment_attachment')) {
            $fileName = time() . '.' . $request->file('comment_attachment')->getClientOriginalExtension();
            $request->file('comment_attachment')->move(public_path('comment_attachment/files'), $fileName);
            $input['comment_attachment'] = $fileName;
        }

        // ---- Handle base64 voice note (same approach as web) ----
        if ($request->filled('voice_note')) {
            $audioFileName = time() . '.wav';
            $audioData = base64_decode(preg_replace('/^data:audio\/\w+;base64,/', '', $request->voice_note));
            file_put_contents(public_path('comment_attachment/audios/') . $audioFileName, $audioData);
            $input['voice_note_path'] = $audioFileName;
        }

        // Create the comment (uses your existing columns)
        $comment = Comment::create([
            'ticket_id'          => $input['ticket_id'],
            'commenter'          => $input['commenter'],
            'description'        => $input['description'] ?? null,
            'comment_attachment' => $input['comment_attachment'] ?? null,
            'voice_note_path'    => $input['voice_note_path'] ?? null,
        ]);

        // Count comments and flip status if this was the first one and ticket is Open
        $commentCount = Comment::where('ticket_id', $ticket->id)->count();
        if ($commentCount == 1 && $ticket->status === 'Open') {
            $ticket->status = 'in_progress';
            $ticket->save();
        }

        // Build payload for notification (same structure you used)
        $notifyPayload = [
            'comment' => $input['description'] ?? null,
            'comment_attachment' => $input['comment_attachment'] ?? null,
            'voice_note' => $request->filled('voice_note') ? $request->voice_note : null,
        ];

        // Determine opposite party and notify
        if ($ticket->requested_by == $user->id) {
            $oppositeUser = User::find($ticket->assigned_to);
        } elseif ($ticket->assigned_to == $user->id) {
            $oppositeUser = User::find($ticket->requested_by);
        } else {
            return response()->json(['success' => false, 'message' => 'Commenter does not belong to this ticket.'], 403);
        }

        if ($oppositeUser) {
            $userData = [
                'id'        => $user->id,
                'ticket_id' => $ticket->id,
                'comment'   => $notifyPayload,
            ];
            $oppositeUser->notify(new CommentAddedNotification($userData));
        }

        return response()->json([
            'success'   => true,
            'thread_id' => $comment->id,
            'message'   => 'Reply posted',
            'data'      => [
                'ticket_id' => $ticket->id,
                'comment'   => [
                    'id'             => $comment->id,
                    'description'    => $comment->description,
                    'attachment'     => $comment->comment_attachment ?? null,
                    'voice_note_path' => $comment->voice_note_path ?? null,
                    'commenter'      => $comment->commenter,
                    'created_at'     => (string) $comment->created_at,
                ],
                'ticket_status' => $ticket->status,
            ],
        ], 201);
    }

    public function apiClose(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'description'        => 'nullable|string',
            'comment_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt,mp3,wav|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
        }

        // === Enforce: only the assigned user can close this ticket ===
        $authId = $request->user()->id;
        if (empty($ticket->assigned_to)) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket is not assigned to anyone, cannot close.',
            ], 409);
        }
        if ((int)$authId !== (int)$ticket->assigned_to) {
            return response()->json([
                'success' => false,
                'message' => 'Only the assigned user can close this ticket.',
            ], 403);
        }
        // =============================================================

        // Mirror original method: set Closed
        $ticket->status = 'Closed';

        // Handle file attachment (same path and name as your web flow)
        $input = $request->all();
        // if ($request->hasFile('comment_attachment')) {
        //     $fileName = time() . '.' . $request->file('comment_attachment')->getClientOriginalExtension();
        //     $request->file('comment_attachment')->move(public_path('comment_attachment/files'), $fileName);
        //     $input['comment_attachment'] = $fileName;
        // }
// Handle file attachment (same path and name as your web flow)
        if ($request->hasFile('comment_attachment')) {
            $fileName = time() . '.' . $request->file('comment_attachment')->getClientOriginalExtension();
            $request->file('comment_attachment')->move(public_path('comment_attachment/files'), $fileName);
            $input['comment_attachment'] = $fileName;
            $input['comment_attachment_url'] = asset('comment_attachment/files/' . $fileName); // ðŸ‘ˆ added
        }

        // Create closing comment from the assigned user
        $comment = Comment::create([
            'ticket_id'          => $ticket->id,
            'commenter'          => $authId,
            'description'        => $input['description'] ?? null,
            'comment_attachment' => $input['comment_attachment'] ?? null,
        ]);

        $ticket->save();

        // Notify requester about closure (same payload style as your web method)
        if ($assignedUser = User::find($ticket->requested_by)) {
            $userData = [
                'id'        => $ticket->assigned_to,
                'ticket_id' => $ticket->id,
            ];
            $commentPayload = [
                'comment'            => $input['description'] ?? null,
                'comment_attachment' => $input['comment_attachment'] ?? null,
            ];
            $assignedUser->notify(new ticketClose($userData, $commentPayload));
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed and comment added successfully.',
            'data'    => [
                'ticket_id'  => $ticket->id,
                'status'     => $ticket->status,
                'comment'    => [
                    'id'         => $comment->id,
                    'description' => $comment->description,
                    'attachment' => $comment->comment_attachment,
                    'attachment_url' => !empty($input['comment_attachment_url'])
                    ? $input['comment_attachment_url']
                    : ($comment->comment_attachment ? asset('comment_attachment/files/' . $comment->comment_attachment) : null), 
                    'commenter'  => $comment->commenter,
                    'created_at' => (string) $comment->created_at,
                ],
            ],
        ], 201);
    }
    
    public function apiFeedbackAdd(Request $request, $id)
    {
    // Validate inputs (same as before)
    $validator = Validator::make($request->all(), [
        'review' => 'nullable|string',
        'rate'   => 'nullable',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    $ticket = Ticket::find($id);
    if (!$ticket) {
        return response()->json(['success' => false, 'message' => 'Ticket not found'], 404);
    }

    // === Enforce: only the requester can provide feedback ===
    $authId = $request->user()->id;
    if ((int)$authId !== (int)$ticket->requested_by) {
        return response()->json([
            'success' => false,
            'message' => 'Only the user who requested this ticket can provide feedback.',
        ], 403);
    }
    
    $input = $request->all();
    $input['ticket_id'] = $ticket->id;
    $input['reviewer']  = $authId;
   
    if ($oppositeUser = User::find($ticket->assigned_to)) {
        $userData = [
            'id'        => $input['reviewer'],
            'ticket_id' => $ticket->id,
        ];
        $comment = [
            'comment'            => $input['review'] ?? null,
            'comment_attachment' => $input['rate']   ?? null, // same mapping as your original
        ];
        $oppositeUser->notify(new FeedbackAdded($userData, $comment));
    }

    // Flip Closed -> Feedback (same as your method)
    if ($ticket->status === 'Closed') {
        $ticket->status = 'Feedback';
        $ticket->save();
    }

    // Persist feedback
    $feedback = TicketFeedback::create($input);
    
        return response()->json([
            'success' => true,
            'message' => 'Feedback added successfully.',
            'data'    => [
                'ticket_id' => $ticket->id,
                'status'    => $ticket->status,
                'feedback'  => $feedback,
            ],
        ], 201);
    }
    
    private function createdAtRange(?string $period): ?array
    {
        $period = strtolower(trim($period ?? 'all'));
        $now = Carbon::now();
    
        switch ($period) {
            case 'last_month': {
                // previous full calendar month
                $start = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $end   = $now->copy()->subMonthNoOverflow()->endOfMonth();
                return [$start, $end];
            }
            case 'last_6_months': {
                // rolling 6 months up to now
                $start = $now->copy()->subMonthsNoOverflow(6)->startOfDay();
                $end   = $now->copy()->endOfDay();
                return [$start, $end];
            }
            case 'last_year': {
                // rolling 12 months up to now
                $start = $now->copy()->subYearNoOverflow()->startOfDay();
                $end   = $now->copy()->endOfDay();
                return [$start, $end];
            }
            default:
                return null; // "all"
        }
    }
    
    private function whenRange(?array $range, callable $cb)
    {
        return $range ? $cb($range[0], $range[1]) : null;
    }
    
    public function apiAssignedDashboard(Request $request)
    {
        $userId  = $request->integer('user_id') ?: $request->user()->id;
        $perPage = max(1, (int) $request->input('per_page', 3));
        $period  = $request->input('period', 'all'); // all|last_month|last_6_months|last_year
        $range   = $this->createdAtRange($period);
    
        // Base builder (requested_by = me) + time range
        $base = Ticket::query()->where('requested_by', $userId);
        if ($range) $base->whereBetween('created_at', $range);
    
        // Totals + status buckets
        $total_tickets = (clone $base)->count();
    
        $incidentStats = [
            'open'        => (clone $base)->where('status', 'Open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'closed'      => (clone $base)->where('status', 'Closed')->count(),
            'feedback'    => (clone $base)->where('status', 'Feedback')->count(),
        ];
    
        // Paginated tickets
       // Paginated tickets
        $tickets = (clone $base)
            ->with([
                'tickettype:id,name',
                'ticketSubType:id,name,priority',
                'requestedby:id,name',
                'assignto:id,name',
            ])
            ->withCount('comments')  
            ->latest('id')
            ->paginate($perPage);
        
        // ➜ Add attachment_url to each ticket
        $assignedItems = $tickets->getCollection()->map(function ($t) {
            $t->attachment_url = $this->buildAttachmentUrl($t->ticket_attachment); // or inline logic
            return $t;
        })->values();

    
        // Priority breakdown
        $priorityQ = DB::table('tickets')
            ->join('ticket_sub_types', 'tickets.ticketsubtype_id', '=', 'ticket_sub_types.id')
            ->select('ticket_sub_types.priority', DB::raw('count(*) as total'))
            ->where('tickets.requested_by', $userId);
    
        if ($range) $priorityQ->whereBetween('tickets.created_at', $range);
    
        $priorityCounts = $priorityQ->groupBy('ticket_sub_types.priority')->get();
    
        $priorityData = ['Urgent' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];
        foreach ($priorityCounts as $row) {
            if (isset($priorityData[$row->priority])) {
                $priorityData[$row->priority] = (int) $row->total;
            }
        }
    
        return response()->json([
            'success' => true,
            'data' => [
                'user_id'       => $userId,
                'period'        => $period,
                'date_range'    => $range ? [$range[0]->toDateTimeString(), $range[1]->toDateTimeString()] : null,
                'total_tickets' => $total_tickets,
                'incidentStats' => $incidentStats,
                'priorityData'  => $priorityData,
                'tickets'       => [
                    'data'  => $tickets->items(),
                    'meta'  => [
                        'current_page' => $tickets->currentPage(),
                        'per_page'     => $tickets->perPage(),
                        'total'        => $tickets->total(),
                        'last_page'    => $tickets->lastPage(),
                        'from'         => $tickets->firstItem(),
                        'to'           => $tickets->lastItem(),
                    ],
                    'links' => [
                        'next' => $tickets->nextPageUrl(),
                        'prev' => $tickets->previousPageUrl(),
                    ],
                ],
            ],
        ]);
    }
    
    public function apiAssigneeDashboard(Request $request)
    {
        $assigneeId = $request->integer('user_id') ?: $request->user()->id;
        $perPage    = max(1, (int) $request->input('per_page', 3));
        $scope      = $request->input('scope', 'me'); // me|team
        $period     = $request->input('period', 'all'); // all|last_month|last_6_months|last_year
        $range      = $this->createdAtRange($period);
    
        // assignee set (me or team)
        $assigneeIds = [$assigneeId];
        if ($scope === 'team') {
            $meEmp = Employee::where('user_id', $assigneeId)->first();
            if ($meEmp && $meEmp->employee_id) {
                $teamUserIds = Employee::where('report_to', $meEmp->employee_id)
                    ->pluck('user_id')->filter()->map(fn($v) => (int) $v)->all();
                $assigneeIds = array_values(array_unique(array_merge($assigneeIds, $teamUserIds)));
            }
        }
    
        // Base with assignees + period
        $base = Ticket::query()->whereIn('assigned_to', $assigneeIds);
        if ($range) $base->whereBetween('created_at', $range);
    
        // Totals
        $total_tickets = (clone $base)->count();
    
        $incidentStats = [
            'open'        => (clone $base)->where('status', 'Open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'closed'      => (clone $base)->where('status', 'Closed')->count(),
            'feedback'    => (clone $base)->where('status', 'Feedback')->count(),
        ];
    
        // Paginated list
        // Paginated tickets
        $tickets = (clone $base)
                ->with([
                    'tickettype:id,name',
                    'ticketSubType:id,name,priority',
                    'requestedby:id,name',
                    'assignto:id,name',
                ])
                ->withCount('comments')  
                ->latest('id')
                ->paginate($perPage);
            
            // ➜ Add attachment_url to each ticket
            $assigneeItems = $tickets->getCollection()->map(function ($t) {
                $t->attachment_url = $this->buildAttachmentUrl($t->ticket_attachment); // or inline logic
                return $t;
            })->values();


    
        // Priority breakdown
        $priorityQ = DB::table('tickets')
            ->join('ticket_sub_types', 'tickets.ticketsubtype_id', '=', 'ticket_sub_types.id')
            ->select('ticket_sub_types.priority', DB::raw('count(*) as total'))
            ->whereIn('tickets.assigned_to', $assigneeIds);
    
        if ($range) $priorityQ->whereBetween('tickets.created_at', $range);
    
        $priorityCounts = $priorityQ->groupBy('ticket_sub_types.priority')->get();
    
        $priorityData = ['Urgent' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0];
        foreach ($priorityCounts as $row) {
            if (isset($priorityData[$row->priority])) {
                $priorityData[$row->priority] = (int) $row->total;
            }
        }
    
        // Team breakdown (only when scope=team)
        $assigneeBreakdown = null;
        if ($scope === 'team' && count($assigneeIds) > 1) {
            $assignees = User::whereIn('id', $assigneeIds)->get(['id', 'name'])->keyBy('id');
    
            $statusQ = DB::table('tickets')
                ->select('assigned_to', 'status', DB::raw('count(*) as total'))
                ->whereIn('assigned_to', $assigneeIds);
            if ($range) $statusQ->whereBetween('created_at', $range);
            $statusCounts = $statusQ->groupBy('assigned_to', 'status')->get();
    
            $prioQ = DB::table('tickets')
                ->join('ticket_sub_types', 'tickets.ticketsubtype_id', '=', 'ticket_sub_types.id')
                ->select('tickets.assigned_to', 'ticket_sub_types.priority', DB::raw('count(*) as total'))
                ->whereIn('tickets.assigned_to', $assigneeIds);
            if ($range) $prioQ->whereBetween('tickets.created_at', $range);
            $prioCounts = $prioQ->groupBy('tickets.assigned_to', 'ticket_sub_types.priority')->get();
    
            $assigneeBreakdown = [];
            foreach ($assigneeIds as $aid) {
                $assigneeBreakdown[$aid] = [
                    'assignee_id'   => $aid,
                    'assignee_name' => $assignees[$aid]->name ?? "User #$aid",
                    'status'        => ['Open' => 0, 'in_progress' => 0, 'Closed' => 0, 'Feedback' => 0],
                    'priority'      => ['Urgent' => 0, 'High' => 0, 'Medium' => 0, 'Low' => 0],
                    'total'         => 0,
                ];
            }
            foreach ($statusCounts as $r) {
                if (isset($assigneeBreakdown[$r->assigned_to]['status'][$r->status])) {
                    $assigneeBreakdown[$r->assigned_to]['status'][$r->status] = (int) $r->total;
                    $assigneeBreakdown[$r->assigned_to]['total'] += (int) $r->total;
                }
            }
            foreach ($prioCounts as $r) {
                if (isset($assigneeBreakdown[$r->assigned_to]['priority'][$r->priority])) {
                    $assigneeBreakdown[$r->assigned_to]['priority'][$r->priority] = (int) $r->total;
                }
            }
            $assigneeBreakdown = array_values($assigneeBreakdown);
        }
    
        // Happiness rating (filtered)
        $ratingQ = DB::table('tickets')
            ->join('ticket_feedback', 'tickets.id', '=', 'ticket_feedback.ticket_id')
            ->whereIn('tickets.assigned_to', $assigneeIds);
        if ($range) $ratingQ->whereBetween('tickets.created_at', $range);
        $avgRating = (float) $ratingQ->avg('ticket_feedback.rate');
        $happinessRating = $avgRating > 0 ? round($avgRating, 2) : 0.0;
    
        return response()->json([
            'success' => true,
            'data'    => [
                'scope'            => $scope,
                'period'           => $period,
                'date_range'       => $range ? [$range[0]->toDateTimeString(), $range[1]->toDateTimeString()] : null,
                'assignee_ids'     => $assigneeIds,
                'total_tickets'    => $total_tickets,
                'incidentStats'    => $incidentStats,
                'priorityData'     => $priorityData,
                'assigneeBreakdown'=> $assigneeBreakdown,
                'happiness_rating' => $happinessRating,
                'tickets'          => [
                    'data'  => $tickets->items(),
                    'meta'  => [
                        'current_page' => $tickets->currentPage(),
                        'per_page'     => $tickets->perPage(),
                        'total'        => $tickets->total(),
                        'last_page'    => $tickets->lastPage(),
                        'from'         => $tickets->firstItem(),
                        'to'           => $tickets->lastItem(),
                    ],
                    'links' => [
                        'next' => $tickets->nextPageUrl(),
                        'prev' => $tickets->previousPageUrl(),
                    ],
                ],
            ],
        ]);
    }

    private function buildAttachmentUrl(?string $name): ?string
    {
        if (!$name) return null;
    
        return Str::startsWith($name, ['http://', 'https://', '//'])
            ? $name
            : asset('ticket_attachment/files/' . ltrim($name, '/'));
    }
    
    private function canSeeTicket($user, Ticket $ticket): bool
    {
        $isAdmin = method_exists($user, 'can') && ($user->can('admindashboard.index') || $user->can('ticket.view'));
        return $isAdmin || in_array((int) $user->id, [(int) $ticket->requested_by, (int) $ticket->assigned_to]);
    }

    private function buildUserCard(?User $user): array
    {
        if (!$user) return [];
    
        $emp = $user->employee;
    
        return [
            'id'           => $user->id,
            'full_name'    => $user->name,
            'designation'  => optional($emp?->designation)->name ?? 'No Designation',
            'employee_id'  => $emp->employee_id ?? null,
            'email'        => $user->email,
            'contact'      => $emp->mobile ??$user->mobile ?? null,
            'department'   => optional($emp?->department)->name ?? 'No Department',
            'region'       => optional($emp?->region)->name ?? 'No Region',
            'sub_region'   => optional($emp?->subregion)->name ?? 'No Subregion',
            'avatar'       => $user->profile_photo_url ?? null,
        ];
    }

    public function requesterCard(Request $request, $id)
    {
        $ticket = Ticket::with([
            'requestedby' => fn ($u) => $u->select('id','name','email')->with([
                'employee' => fn ($e) => $e->select(
                    'id','user_id','employee_id',
                    'designation_id','department_id','region_ids','sub_region_id','mobile'
                )->with([
                    'designation:id,name',
                    'department:id,name',
                    'subregion:id,name',
                ]),
            ]),
        ])->find($id);
    
        if (!$ticket) {
            return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
        }
        if (!$this->canSeeTicket($request->user(), $ticket)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }
    
        $user = $ticket->requestedby;
        $emp  = optional($user)->employee;
    
        // --- DEBUG (uncomment for one call to verify) ---
        // return response()->json([
        //     'region_ids_raw' => $emp?->region_ids,
        //     'accessor_region_names' => $emp?->region_names ?? '(accessor empty)'
        // ]);
    
        // Build your existing card (whatever type it returns)
        $card = $this->buildUserCard($user);
    
        // Make sure we have a mutable array, even if buildUserCard() returned a Resource/Collection/object
        if (is_object($card) && method_exists($card, 'toArray')) {
            $card = $card->toArray(request());
        } elseif ($card instanceof \JsonSerializable) {
            $card = (array) $card->jsonSerialize();
        } elseif ($card instanceof \Illuminate\Support\Collection) {
            $card = $card->toArray();
        } elseif (!is_array($card)) {
            $card = (array) $card; // last resort
        }
    
        // Compute region_names directly from DB to be 100% sure it's available
        $regionNames = '';
        if ($emp && is_array($emp->region_ids) && count($emp->region_ids)) {
            $regionNames = Region::whereIn('id', $emp->region_ids)
                ->orderBy('name')
                ->pluck('name')
                ->implode(', ');
        }
    
        // Inject it regardless of your card shape:
        // 1) If your card has an "employee" sub-array:
        if (isset($card['employee']) && is_array($card['employee'])) {
            $card['employee']['region'] = $regionNames;
        } else {
            // 2) If your builder flattens fields, put it at top level too (harmless if duplicated)
            $card['region'] = $regionNames;
        }
    
        return response()->json([
            'status'  => true,
            'message' => 'Requester card fetched',
            'data'    => [
                'ticket_id' => $ticket->id,
                'role'      => 'requester',
                'user'      => $card,
            ],
        ], 200);
    }

    public function assigneeCard(Request $request, $id)
    {
        $ticket = Ticket::with([
            'assignto' => fn ($u) => $u->select('id','name','email')->with([
                'employee' => fn ($e) => $e->select(
                    'id','user_id','employee_id',
                    'designation_id','department_id','region_ids','sub_region_id','mobile'
                )->with([
                    'designation:id,name',
                    'department:id,name',
                    'subregion:id,name',
                ]),
            ]),
        ])->find($id);
    
        if (!$ticket) {
            return response()->json(['status' => false, 'message' => 'Ticket not found'], 404);
        }
        if (!$this->canSeeTicket($request->user(), $ticket)) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }
        if (!$ticket->assignto) {
            return response()->json(['status' => false, 'message' => 'No assignee set'], 409);
        }
    
        $user = $ticket->assignto;
        $emp  = optional($user)->employee;
    
        // Build your existing card (could be Resource/Collection/array)
        $card = $this->buildUserCard($user);
    
        // Ensure $card is a mutable array
        if (is_object($card) && method_exists($card, 'toArray')) {
            $card = $card->toArray(request());
        } elseif ($card instanceof \JsonSerializable) {
            $card = (array) $card->jsonSerialize();
        } elseif ($card instanceof \Illuminate\Support\Collection) {
            $card = $card->toArray();
        } elseif (!is_array($card)) {
            $card = (array) $card;
        }
    
        // Compute region_names directly (works even if accessor gets stripped)
        $regionNames = '';
           
        if ($emp && is_array($emp->region_ids) && count($emp->region_ids)) {
            $regionNames = Region::whereIn('id', $emp->region_ids)
                ->orderBy('name')
                ->pluck('name')
                ->implode(', ');
        }
        // Inject into the card
        if (isset($card['employee']) && is_array($card['employee'])) {
            $card['employee']['region'] = $regionNames;
        } else {
            // if your builder flattens employee fields
            $card['region'] = $regionNames;
        }
    
        return response()->json([
            'status'  => true,
            'message' => 'Assignee card fetched',
            'data'    => [
                'ticket_id' => $ticket->id,
                'role'      => 'assignee',
                'user'      => $card,
            ],
        ], 200);
    }

    public function getComments(Request $request, Ticket $ticket)
    {
        // Permission: admin OR requester OR assignee
        $user = $request->user();
        // $isAdmin = method_exists($user, 'can') && $user->can('admindashboard.index') ;
        // if (! $isAdmin && ! in_array((int)$user->id, [(int)$ticket->requested_by, (int)$ticket->assigned_to])) {
        //     return response()->json(['status' => false,     'message' => 'You do not have permission to view or perform this action on this ticket.'], 200);
        // }
    
        $base = rtrim(config('app.url') ?: url('/'), '/');
    
        $perPage = (int) $request->query('per_page', 100);
    
        $comments = $ticket->comments()
            ->with(['commenter_user:id,name'])
            ->orderBy('created_at', 'asc')
            ->get(['id','ticket_id','commenter','description','comment_attachment','voice_note_path','created_at']);
    
        $data = $comments->map(function ($c) use ($base) {
            $fileUrl  = $c->comment_attachment ? "{$base}/comment_attachment/files/{$c->comment_attachment}" : null;
            $audioUrl = $c->voice_note_path    ? "{$base}/comment_attachment/audios/{$c->voice_note_path}" : null;
    
            return [
                'id'                 => $c->id,
                'ticket_id'          => $c->ticket_id,
                'commenter_id'       => $c->commenter,
                'commenter_name'     => optional($c->commenter_user)->name,
                'description'        => $c->description,
                'attachment_url'     => $fileUrl,
                'voice_note_url'     => $audioUrl,
                'created_at'         => (string) $c->created_at,
            ];
        });
    
        // === Add feedback if ticket status is "Feedback" ===
        $feedback = null;
        if (strtolower($ticket->status) === 'feedback') {
            $feedback = \DB::table('ticket_feedback')
                ->where('ticket_id', $ticket->id)
                ->select('review','rate')
                ->first();
    
            if (!$feedback) {
                $feedback = (object)[ 'review' => null, 'rate' => null ];
            }
        } else {
            $feedback = (object)[ 'review' => null, 'rate' => null ];
        }
    
        return response()->json([
            'status'       => true,
            'message'      => 'Comments fetched',
            'employee_id'  => (int) $ticket->requested_by,
            'manager_id'   => (int) $ticket->assigned_to,
            'review'       => $feedback->review,
            'rate'         => $feedback->rate,
            'data'         => $data,
            'meta'         => ['count' => $data->count()],
        ]);
    }

    public function candidates(Request $request, Ticket $ticket)
    {
        // Basic guard: only admins, current assignee, or requester can fetch candidates
        $user = $request->user();
        if (! $this->canTouch($user, $ticket)) {
            return response()->json(['status'=>false,'message'=>'Forbidden'], 403);
        }

        $prevUserId = (int) ($ticket->prev_assigned ?? 0);

        $assignedEmp = Employee::where('user_id', $ticket->assigned_to)->first();
        if (! $assignedEmp) {
            return response()->json(['status'=>false,'message'=>'Assigned employee not found.'], 404);
        }

        $candidates = collect();

        if ($prevUserId) {
            // try to use previous assignee's employee row and return that single user
            $prevEmp = Employee::where('user_id', $prevUserId)->first();
            if ($prevEmp) {
                $userRow = User::where('id', $prevEmp->user_id)->first(['id','name','email']);
                if ($userRow) $candidates = collect([$userRow]);
            }
        }

        if ($candidates->isEmpty()) {
            // fall back: direct reports of current assignee (excluding the requester)
            $userIds = Employee::where('report_to', $assignedEmp->employee_id)
                ->whereNotIn('user_id', [$ticket->requested_by])
                ->pluck('user_id')
                ->toArray();

            $candidates = User::whereIn('id', $userIds)
                ->get(['id','name','email']);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Reassign candidates fetched.',
            'data'    => $candidates->values(),
            'meta'    => [
                'ticket_id'     => $ticket->id,
                'prev_assigned' => $prevUserId ?: null,
                'assigned_to'   => (int) $ticket->assigned_to,
            ],
        ]);
    }

    public function reassign(Request $request, Ticket $ticket)
    {
        // Only admins OR current assignee can reassign
        $user = $request->user();
        $isAdmin = method_exists($user, 'can') && $user->can('admindashboard.index');
        if (! $isAdmin && (int)$user->id !== (int)$ticket->assigned_to) {
            return response()->json(['status'=>false,'message'=>'Only admin or current assignee can reassign.'], 403);
        }

        $v = Validator::make($request->all(), [
            'assign_to' => ['required','integer','exists:users,id'],
        ]);
        if ($v->fails()) {
            return response()->json(['status'=>false,'errors'=>$v->errors()], 422);
        }

        $assignTo = (int) $request->integer('assign_to');

        // Ensure assign_to is among valid candidates (same rules as candidates())
        $validIds = $this->candidateIds($ticket);
        if (! in_array($assignTo, $validIds, true)) {
            return response()->json([
                'status'=>false,
                'message'=>'Selected user is not a valid candidate for reassignment.'
            ], 422);
        }

        // nothing to do?
        if ((int)$ticket->assigned_to === $assignTo) {
            return response()->json(['status'=>true,'message'=>'Already assigned to this user.']);
        }

        // perform reassignment, keep prev_assigned
        $ticket->prev_assigned = $ticket->assigned_to;
        $ticket->assigned_to   = $assignTo;
        $ticket->current_level   = 1;
        $ticket->save();

        // timeline
        TicketTimeline::create([
            'ticket_id'  => $ticket->id,
            'assign_to'  => $assignTo,
            'assign_time'=> now(),
            'assign_type'=> 'By Reassign',
        ]);

        // notify the new assignee (same notification your web code uses)
        if ($newAssignee = User::find($assignTo)) {
            $newAssignee->notify(new ResignTicket([
                'id'        => $ticket->requested_by,
                'ticket_id' => $ticket->id,
            ]));
        }

        return response()->json([
            'status'  => true,
            'message' => 'Ticket reassigned successfully.',
            'data'    => [
                'ticket_id'   => $ticket->id,
                'assigned_to' => $assignTo,
                'prev_assigned'=> (int) $ticket->prev_assigned,
            ],
        ], 200);
    }

    private function canTouch(User $user, Ticket $ticket): bool
    {
        $isAdmin = method_exists($user, 'can') && $user->can('admindashboard.index');
        return $isAdmin
            || (int)$user->id === (int)$ticket->assigned_to
            || (int)$user->id === (int)$ticket->requested_by;
    }

    private function candidateIds(Ticket $ticket): array
    {
        $ids = [];

        // 1) try previous assignee
        if ($ticket->prev_assigned) {
            $prevEmp = Employee::where('user_id', $ticket->prev_assigned)->first();
            if ($prevEmp) $ids[] = (int) $prevEmp->user_id;
        }

        // 2) direct reports of current assignee (excluding requester)
        $assignedEmp = Employee::where('user_id', $ticket->assigned_to)->first();
        if ($assignedEmp) {
            $childUserIds = Employee::where('report_to', $assignedEmp->employee_id)
                ->whereNotIn('user_id', [$ticket->requested_by])
                ->pluck('user_id')->map(fn($v)=>(int)$v)->toArray();

            $ids = array_merge($ids, $childUserIds);
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
