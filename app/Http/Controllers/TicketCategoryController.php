<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketCategoryRequest;
use App\Models\TicketCategory;
use App\Models\TicketType;
use Illuminate\Http\Request;

class TicketCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:ticketCategory.index')->only('index');
        $this->middleware('can:ticketCategory.create')->only('create', 'store');
        $this->middleware('can:ticketCategory.edit')->only('edit', 'update');
        $this->middleware('can:ticketCategory.delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $perPage = \App\Support\ListingFilters::perPage($request);
        $search = \App\Support\ListingFilters::search($request);
        $categories = \App\Support\ListingFilters::paginate(TicketCategory::with('ticketType')->orderByDesc('id'), $request,
            ['name', 'code', 'created_at', 'updated_at'], ['ticketType' => ['name']]);
        return view('ticketcategories.index', compact('categories', 'search', 'perPage'));
    }

    public function create()
    {
        return view('ticketcategories.create', ['tickettypes' => TicketType::orderBy('name')->get()]);
    }

    public function store(TicketCategoryRequest $request)
    {
        TicketCategory::create($request->validated());
        return redirect()->route('ticketcategories.index')->with('success', 'Create successfully!');
    }

    public function edit(TicketCategory $ticketcategory)
    {
        return view('ticketcategories.edit', ['category' => $ticketcategory, 'tickettypes' => TicketType::orderBy('name')->get()]);
    }

    public function update(TicketCategoryRequest $request, TicketCategory $ticketcategory)
    {
        $ticketcategory->update($request->validated());
        return redirect()->route('ticketcategories.index')->with('success', 'Update successfully!');
    }

    public function destroy(Request $request, TicketCategory $ticketcategory)
    {
        if ($ticketcategory->subCategories()->exists() || $ticketcategory->tickets()->exists()) {
            $message = 'This Ticket Category cannot be deleted because it has Ticket Sub-Categories or tickets.';
            if ($request->expectsJson() || $request->ajax()) return response()->json(['message' => $message], 409);
            return redirect()->route('ticketcategories.index')->with('error', $message);
        }
        $ticketcategory->delete();
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['deleted', 'Ticket category has been deleted']);
        }
        return redirect()->route('ticketcategories.index')->with('success', 'Ticket category has been deleted');
    }
}
