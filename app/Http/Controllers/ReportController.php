<?php

namespace App\Http\Controllers;

use App\Exports\FifthTabExport;
use App\Exports\FirstTabExport;
use App\Exports\FourthTabExport;
use App\Exports\SecondTabExport;
use App\Exports\ThirdTabExport;
use Illuminate\Http\Request;
// use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;
use App\Models\Ticket;
use DB;
use Barryvdh\DomPDF\Facade\Pdf ;

class ReportController extends Controller
{
    public function index()
    {
        RolesPermissionController::can(['report.index']);
        $assignedUsers = DB::table('tickets')
            ->join('users', 'tickets.assigned_to', '=', 'users.id')
            ->select('users.id', 'users.name')
            ->distinct()
            ->get();

        return view('report.index', ['assignedUsers' => $assignedUsers]);
    }

    private function applyFilters(Request $request)
    {
       
        $tab = $request->query('tab', 1);
        $filters = [];
        $filename = '';
        $tableFile = '';


        switch ($tab) {
            case 1:
                $query = Ticket::with([
                    'tickettype:id,name',
                    'ticketsubtype:id,name,ticket_category_id',
                    'ticketsubtype.category', 'ticketCategory',
                    'requestedby:id,name',
                    'assignto:id,name',
                ]);
                if (!$request->has(['status', 'startDate', 'endDate'])) {
                    return response()->json(['error' => 'Missing required fields for tab 1'], 400);
                }
                $query->where('status', $request->status)
                    ->whereBetween('created_at', [$request->startDate, $request->endDate]);
                $filters = $request->only('status', 'startDate', 'endDate');
                $filename = 'Ticket Detail Report';
                $tableFile = 'report.table';
                break;

            case 2:
                $query = Ticket::with([
                    'tickettype:id,name',
                    'ticketsubtype:id,name,ticket_category_id',
                    'ticketsubtype.category', 'ticketCategory',
                    'requestedby:id,name',
                    'assignto:id,name',
                ])
                    ->selectRaw('tickettype_id, ticketsubtype_id, 
                    SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_count,
                    SUM(CASE WHEN status = "inprogress" THEN 1 ELSE 0 END) as inprogress_count,
                    SUM(CASE WHEN status = "feedback" THEN 1 ELSE 0 END) as feedback_count')
                    ->whereBetween('created_at', [$request->startDate, $request->endDate])
                    ->groupBy('tickettype_id', 'ticketsubtype_id');


                if (!$request->has(['startDate', 'endDate'])) {
                    return response()->json(['error' => 'Missing required fields for tab 2'], 400);
                }
                $filters = $request->only('startDate', 'endDate');
                $filename = 'Ticket Summary Report';
                $tableFile = 'report.table1';
                break;

            case 3:
                $query = Ticket::with([
                    'tickettype:id,name',
                    'ticketsubtype:id,name,ticket_category_id',
                    'ticketsubtype.category', 'ticketCategory',

                ])->select('id', 'tickettype_id', 'ticket_category_id', 'ticketsubtype_id', 'status'); // specify the fields for Ticket model;

                if (!$request->has(['user', 'startDate', 'endDate'])) {
                    return response()->json(['error' => 'Missing required fields for tab 3'], 400);
                }
                $query->where('assigned_to', $request->user)
                    ->whereBetween('created_at', [$request->startDate, $request->endDate]);
                $filters = $request->only('user', 'userName', 'startDate', 'endDate');
                $filename = 'Ticket Assignment Report';
                $tableFile = 'report.table2';

                break;

            case 4:
                $query = Ticket::with([
                    'tickettype:id,name',
                    'ticketsubtype:id,name,ticket_category_id',
                    'ticketsubtype.category', 'ticketCategory',

                ])->select('id', 'tickettype_id', 'ticket_category_id', 'ticketsubtype_id', 'status');

                if (!$request->has(['startDate', 'endDate'])) {
                    return response()->json(['error' => 'Missing required fields for tab 4'], 400);
                }
                $query->whereBetween('created_at', [$request->startDate, $request->endDate]);
                $filters = $request->only('startDate', 'endDate');
                $filename = 'Ticket Status Report';
                $tableFile = 'report.table2';
                break;

            case 5:
                $query = Ticket::with(['tickettype', 'ticketsubtype.category', 'ticketCategory',])->where('assigned_to', $request->user)
                    ->whereBetween('tickets.created_at', [$request->startDate, $request->endDate])
                    ->join('ticket_feedback', 'tickets.id', '=', 'ticket_feedback.ticket_id')
                    ->select('tickets.*', 'ticket_feedback.rate');

                if (!$request->has(['user', 'startDate', 'endDate'])) {
                    return response()->json(['error' => 'Missing required fields for tab 3'], 400);
                }
                $filters = $request->only('user', 'userName', 'startDate', 'endDate');
                $filename = 'Happiness Rating Report';
                $tableFile = 'report.table3';

                break;

            default:
                return response()->json(['error' => 'Invalid tab selected'], 400);
        }

        return compact('query', 'filters', 'filename', 'tableFile');
    }


    public function viewPDF(Request $request)
    {
        RolesPermissionController::can(['report.index']);
        $filterResponse = $this->applyFilters($request);
    
        if ($filterResponse instanceof \Illuminate\Http\JsonResponse) {
            return $filterResponse;
        }
    
        $query = $filterResponse['query'];
        $filters = $filterResponse['filters'];
        $filename = $filterResponse['filename'];
        $tableFile = $filterResponse['tableFile'];
    
        $ticketData = $query->get();
    
        $tableContent = view($tableFile, ['ticketData' => $ticketData])->render();
        
        // Dynamic header based on the tab query parameter
        $headerView = 'report.header' . $request->query('tab', 1);

        $headerTitle = $request->input('headerTitle', 'Ticket Detail Report 1');
        $headerContent = view($headerView, ['filterSummary' => $filters])->render();
    
        // Footer content
        $footerContent = view('report.footer')->render();
    
        // Combine header, body, and footer into a single PDF-friendly layout
        $pdfContent = view('report.pdf', [
            'header' => $filename,
            'body' => $tableContent,
            'footer' => $footerContent,
            'title' => $filename,
            'filterSummary' => $filters,
            'activeTab' => $request->query('tab', 1)
        ])->render();
    
        $pdf = Pdf::loadHTML($pdfContent)
            ->setPaper('a4', 'portrait'); // Set paper size and orientation if needed
    
        return $pdf->stream($filename); // Use download() to download directly
    }
    

    public function downloadExcel(Request $request)
    {
        RolesPermissionController::can(['report.index']);
        $filterResponse = $this->applyFilters($request);

        if ($filterResponse instanceof \Illuminate\Http\JsonResponse) {
            return $filterResponse;
        }

        // $ticketData = $filterResponse['query']->get();
        //
        $filename = $filterResponse['filename'];

        switch ($filename) {
            case 'Ticket Detail Report':
                $data = $filterResponse['query']->get(); // Modify this to fetch the correct data for the first tab
                return Excel::download(new FirstTabExport($data, ), "{$filename}.xlsx");

            case 'Ticket Summary Report':
                $data = $filterResponse['query']->get();
                return Excel::download(new SecondTabExport($data, ), "{$filename}.xlsx");

            case 'Ticket Assignment Report':
                $data = $filterResponse['query']->get();
                return Excel::download(new ThirdTabExport($data, ), "{$filename}.xlsx");
            case 'Ticket Status Report':
                $data = $filterResponse['query']->get();
                return Excel::download(new FourthTabExport($data, ), "{$filename}.xlsx");
            case 'Happiness Rating Report':
                $data = $filterResponse['query']->get();
                return Excel::download(new FifthTabExport($data, ), "{$filename}.xlsx");

            default:
                return response()->json(['error' => 'Invalid tab selected'], 400);
        }

    }
}
