{{-- resources/views/tickets/index.blade.php --}}
@extends('layouts.main')

@section('breadcrumb')
<style>
  #tickets-table_filter { display: none !important; }
</style>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
    <li class="breadcrumb-item text-sm">
      <a class="text-white" href="javascript:;"><i class="ni ni-box-2"></i></a>
    </li>
    <li class="breadcrumb-item text-sm text-white active">
      <a class="opacity-5 text-white" href="javascript:;">Tickets List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Tickets List</h6>
</nav>
@endsection

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">

      {{-- Header --}}
      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Tickets</h5>
            <p class="text-sm mb-0">List of Tickets</p>
          </div>
          <div class="ms-auto d-flex my-auto mt-lg-0 mt-4">
            @can('ticket.create')
              <a href="{{ route('tickets.create') }}" class="btn bg-gradient-primary btn-sm">+ Add New</a>
            @endcan
            @can('admindashboard.index')
              <a href="{{ route('ticketShow.all') }}" class="btn bg-gradient-primary btn-sm ms-3">+ Show All</a>
            @endcan
          </div>
        </div>
      </div>

      <form method="GET"
            action="{{ route('tickets.index') }}"
            class="d-flex align-items-center w-100 px-4 py-3"
            style="border-bottom:1px solid #ddd; gap:1rem;">
        <input type="hidden" name="tab" value="{{ request('tab','my-tickets') }}">

        
        <div style="min-width:150px;">
          <select name="perpage" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach(['5','10','20','50','all'] as $opt)
              <option value="{{ $opt }}" {{ request('perpage')==$opt ? 'selected':'' }}>
                {{ $opt==='all' ? 'All' : $opt }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Centered count --}}
        <div class="flex-grow-1 text-center">
          @php $tab = request('tab','my-tickets'); @endphp
          @if($tab==='my-tickets')
            @if($tickets->total())
              Showing {{ $tickets->firstItem() }} to {{ $tickets->lastItem() }} of {{ $tickets->total() }} entries
            @else
              No entries found
            @endif
          @else
            @if($team->total())
              Showing {{ $team->firstItem() }} to {{ $team->lastItem() }} of {{ $team->total() }} entries
            @else
              No entries found
            @endif
          @endif
        </div>

        {{-- Search box --}}
        <div class="d-flex align-items-center" style="min-width:300px; justify-content:flex-end;">
          <input type="text"
                 name="search"
                 value="{{ request('search') }}"
                 placeholder="Type, Subtype, User, Status..."
                 class="form-control form-control-sm me-2"
                 onkeydown="if(event.key==='Enter'){ this.form.submit(); }">
          <button type="submit" class="btn btn-sm btn-primary">Search</button>
        </div>
      </form>

      {{-- Tabs --}}
      <ul class="nav nav-tabs mt-3" id="ticketTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link {{ $activeTab==='my-tickets' ? 'active':'' }}"
             href="{{ request()->fullUrlWithQuery(['tab' => 'my-tickets', 'page' => 1]) }}" role="tab">My Tickets</a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ $activeTab==='team-tickets' ? 'active':'' }}"
             href="{{ request()->fullUrlWithQuery(['tab' => 'team-tickets', 'page' => 1]) }}" role="tab">Team Tickets</a>
        </li>
      </ul>

      {{-- Tab Content --}}
      <div class="tab-content" id="ticketTabsContent">

        {{-- My Tickets Pane --}}
        <div class="tab-pane fade {{ $activeTab==='my-tickets' ? 'show active':'' }}"
             id="my-tickets" role="tabpanel">
          <div class="table-responsive p-3">
            <table class="table table-striped" id="my-tickets-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Ticket Type</th>
                  <th>Ticket Subtype</th>
                  <th>Comments</th>
                  <th>Requested By</th>
                  <th>Assign To</th>
                  <th>Status</th>
                  <th>Aging</th>
                  <th>Created At</th>
                  @canany(['tickets.edit','ticket.view'])<th>Action</th>@endcanany
                </tr>
              </thead>
              <tbody>
                @foreach($tickets as $ticket)
                <tr>
                  <td>{{ $tickets->firstItem() + $loop->index }}</td>
                  <td>{{ $ticket->tickettype->name }}</td>
                  <td>{{ $ticket->ticketsubtype->name ?? 'N/A' }}</td>
                  <td>{{ $ticket->comments->count() }}</td>
                  <td>{{ $ticket->requestedby->name }}</td>
                  <td>{{ $ticket->assignto->name }}</td>
                  <td>{{ $ticket->status }}</td>
                  <td>{{ $ticket->created_at->diffForHumans() }}</td>
                  <td>{{ $ticket->created_at->format('Y-m-d') }}</td>
                  @canany(['tickets.edit','ticket.view'])
                  <td>
                    @can('ticket.view')
                      <a href="{{ route('ticket.view', ['id' => $ticket->id, 'return_to' => request()->fullUrlWithQuery(['tab' => 'my-tickets'])]) }}">
                        <i class="fas fa-eye text-primary"></i>
                      </a>
                    @endcan
                    @can('tickets.edit')
                      @if($ticket->status==='Open')
                        <a href="{{ route('tickets.edit', ['ticket' => $ticket->id, 'return_to' => request()->fullUrlWithQuery(['tab' => 'my-tickets'])]) }}">
                          <i class="fas fa-edit text-purple"></i>
                        </a>
                      @endif
                    @endcan
                  </td>
                  @endcanany
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center px-4 py-3">
            {{ $tickets
                ->appends(['tab'=>'my-tickets','perpage'=>request('perpage'),'search'=>request('search')])
                ->links('pagination::bootstrap-4') }}
          </div>
        </div>

        {{-- Team Tickets Pane --}}
        <div class="tab-pane fade {{ $activeTab==='team-tickets' ? 'show active':'' }}"
             id="team-tickets" role="tabpanel">
          @if($team->isEmpty())
            <p class="text-sm px-3 pt-3 mb-0">No team tickets found.</p>
          @endif
          <div class="table-responsive p-3">
            <table class="table table-striped" id="team-tickets-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Ticket Type</th>
                  <th>Ticket Subtype</th>
                  <th>Comments</th>
                  <th>Requested By</th>
                  <th>Assign To</th>
                  <th>Status</th>
                  <th>Aging</th>
                  <th>Created At</th>
                  @canany(['tickets.edit','ticket.view'])<th>Action</th>@endcanany
                </tr>
              </thead>
              <tbody>
                @foreach($team as $ticket)
                <tr>
                  <td>{{ $team->firstItem() + $loop->index }}</td>
                  <td>{{ $ticket->tickettype->name }}</td>
                  <td>{{ $ticket->ticketsubtype->name ?? 'N/A' }}</td>
                  <td>{{ $ticket->comments->count() }}</td>
                  <td>{{ $ticket->requestedby->name }}</td>
                  <td>{{ $ticket->assignto->name }}</td>
                  <td>{{ $ticket->status }}</td>
                 <td>{{ $ticket->created_at->diffForHumans() }}</td>
                  <td>{{ $ticket->created_at->format('Y-m-d') }}</td>
                  @canany(['tickets.edit','ticket.view'])
                  <td>
                    @can('ticket.view')
                      <a href="{{ route('ticket.view', ['id' => $ticket->id, 'return_to' => request()->fullUrlWithQuery(['tab' => 'team-tickets'])]) }}">
                        <i class="fas fa-eye text-primary"></i>
                      </a>
                    @endcan
                    @can('tickets.edit')
                      @if($ticket->status==='Open')
                        <a href="{{ route('tickets.edit', ['ticket' => $ticket->id, 'return_to' => request()->fullUrlWithQuery(['tab' => 'team-tickets'])]) }}">
                          <i class="fas fa-edit text-purple"></i>
                        </a>
                      @endif
                    @endcan
                  </td>
                  @endcanany
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center px-4 py-3">
            {{ $team
                ->appends(['tab'=>'team-tickets','perpage'=>request('perpage'),'search'=>request('search')])
                ->links('pagination::bootstrap-4') }}
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@section('script')
<script>
  $(function(){
    $('#my-tickets-table, #team-tickets-table').DataTable({
      paging:   false,
      ordering: true,
      info:     false,
      searching:false
    });
  });
</script>
@endsection
