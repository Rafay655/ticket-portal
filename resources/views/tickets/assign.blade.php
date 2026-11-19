@extends('layouts.main')

@section('breadcrumb')
<style>#tickets-table_filter{display:none!important;}</style>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
    <li class="breadcrumb-item text-sm">
      <a class="text-white" href="#"><i class="ni ni-box-2"></i></a>
    </li>
    <li class="breadcrumb-item text-sm text-white active">
      <a class="opacity-5 text-white" href="#">Assigned Tickets List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Assigned Tickets List</h6>
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
            <h5 class="mb-0">Assigned Tickets</h5>
            <p class="text-sm mb-0">List of Assigned Tickets</p>
          </div>
        </div>
      </div>

      {{-- Controls: per-page, count, search --}}
      <form method="GET"
            action="{{ route('tickets.assign') }}"
            class="d-flex align-items-center w-100 px-4 py-3"
            style="border-bottom:1px solid #ddd; gap:1rem;">
        {{-- Per-page --}}
        <div style="min-width:150px;">
          <select name="perpage"
                  class="form-select form-select-sm"
                  onchange="this.form.submit()">
            @foreach(['5','10','20','50','all'] as $opt)
              <option value="{{ $opt }}"
                      {{ request('perpage') == $opt ? 'selected':'' }}>
                {{ $opt === 'all' ? 'All' : $opt }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Centered count --}}
        <div class="flex-grow-1 text-center">
          @if($tickets->total())
            Showing {{ $tickets->firstItem() }}
            to {{ $tickets->lastItem() }}
            of {{ $tickets->total() }} entries
          @else
            No entries found
          @endif
        </div>

        {{-- Search box --}}
        <div class="d-flex align-items-center" style="min-width:300px; justify-content:flex-end;">
          <input type="text"
                 name="search"
                 value="{{ $search }}"
                 placeholder="Type, Subtype, User, Status..."
                 class="form-control form-control-sm me-2"
                 onkeydown="if(event.key==='Enter'){ this.form.submit(); }">
          <button type="submit" class="btn btn-sm btn-primary">Search</button>
        </div>
      </form>

      {{-- Table --}}
      <div class="table-responsive p-3">
        <table class="table table-striped table-hover" id="tickets-table">
          <thead class="thead-light">
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
              <th>Action</th>
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
              <td>
                <a href="{{ route('ticket.view', ['id' => $ticket->id, 'return_to' => request()->fullUrl()]) }}" class="mx-1" title="View">
                  <i class="fas fa-eye text-primary"></i>
                </a>
                @if($ticket->current_level >= 2)
                  <a href="{{ route('tickets.reassign', ['id' => $ticket->id, 'return_to' => request()->fullUrl()]) }}" class="mx-1" title="Reassign">
                    <i class="fas fa-repeat text-primary"></i>
                  </a>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="d-flex justify-content-between align-items-center px-4 py-3">
        {{ $tickets
            ->appends(['perpage'=>request('perpage'),'search'=>request('search')])
            ->links('pagination::bootstrap-4') }}
      </div>

    </div>
  </div>
</div>
@endsection

@section('script')
<script>
  $(document).ready(function(){
    $('#tickets-table').DataTable({
      paging:   false,
      ordering: true,
      info:     false,
      searching:false
    });
  });
</script>
@endsection
