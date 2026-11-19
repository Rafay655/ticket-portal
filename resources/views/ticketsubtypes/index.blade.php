@extends('layouts.main')
@section('breadcrumb')
<style>
  #ticketsubtypes-table_filter {
    display: none !important;
  }
</style>
<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
    <li class="breadcrumb-item text-sm">
      <a class="text-white" href="javascript:;">
        <i class="ni ni-box-2"></i>
      </a>
    </li>
    <li class="breadcrumb-item text-sm text-white active">
      <a class="opacity-5 text-white" href="javascript:;">Ticket Sub Type List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Ticket Sub Type List</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">

      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Ticket Sub Types</h5>
            <p class="text-sm mb-0">List of Ticket Sub Types</p>
          </div>
          @can('ticketSubType.create')
          <a href="{{ route('ticketsubtypes.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+&nbsp; Add New</a>
          @endcan
        </div>
      </div>

      <!-- Controls Row -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #ddd;">
        <!-- Per Page Dropdown -->
        <form method="GET" action="{{ route('ticketsubtypes.index') }}" style="width:200px;" class="d-flex align-items-center">
          <label for="perpage" class="me-2" style="min-width: 70px; font-weight: 500;">Per Page:</label>
          <select name="perpage" id="perpage" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="5" {{ $perPage == 5 ? 'selected' : '' }}>5</option>
            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
            <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
          </select>
        </form>

        <!-- Search Bar -->
        <div style="width: 250px;">
          <input type="text" id="search-bar" placeholder="Search Ticket Sub Types..." class="form-control form-control-sm">
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive p-3">
        <table class="table table-striped table-hover" id="ticketsubtypes-table">
          <thead class="thead-light">
            <tr>
              <th>Sr#</th>
              <th>Name</th>
              <th>Priority</th>
              @canany(['ticketSubType.edit', 'ticketSubType.delete'])
              <th>Action</th>
              @endcanany
            </tr>
          </thead>
          <tbody>
            @foreach($ticketsubtypes as $ticketsubtype)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $ticketsubtype->name ?? "-" }}</td>
              <td>{{ $ticketsubtype->priority ?? "-" }}</td>
              @canany(['ticketSubType.edit', 'ticketSubType.delete'])
              <td>
                <div class="d-flex align-items-center">
                  @can('ticketSubType.edit')
                  <a href="{{ route('ticketsubtypes.edit', $ticketsubtype->id) }}" class="me-3" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <i class="fas fa-user-edit text-purple"></i>
                  </a>
                  @endcan
                  @can('ticketSubType.delete')
                  <x-forms.action-btn href="" id="table_data_delete" action="delete"
                                                title="delete ticket sub type"
                                                data-action="{{route('ticketsubtypes.destroy', $ticketsubtype['id'])}}"
                                                data-parent="tr"></x-forms.action-btn>
                  @endcan
                </div>
              </td>
              @endcanany
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3">
        <div>
          {{ $ticketsubtypes->links('pagination::bootstrap-4') }}
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('style')
<link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap.css" rel="stylesheet" />
@endsection

@section('script')
<script>
  $(document).ready(function () {
    // Enable DataTables
    let table = $('#ticketsubtypes-table').DataTable({
      paging: false, // Disable DataTables pagination to use Laravel's
      ordering: true, // Enable column sorting
      info: false // Disable DataTables info to use Laravel's pagination
    });

    // Add search functionality to the custom search bar
    $('#search-bar').on('keyup', function () {
      table.search(this.value).draw();
    });
  });
</script>
@endsection
