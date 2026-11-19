@extends('layouts.main')
@section('breadcrumb')
<style>
  #employees-table_filter {
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
      <a class="opacity-5 text-white" href="javascript:;">Employees List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Employees List</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">

      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Employees</h5>
            <p class="text-sm mb-0">List of Employees</p>
          </div>
          <div>
            @can('employee.create')
              <a href="{{ route('employees.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+ Add New</a>
            @endcan
            @can(['employee.delete','admindashboard.index'])
              <a href="{{ route('employee.separation.index') }}" class="btn bg-gradient-primary btn-sm mb-0">+ Separated Employees</a>
            @endcan
          </div>
        </div>
      </div>

      <!-- Controls Row: Per-page, Count, Search -->
      <form method="GET" action="{{ route('employees.index') }}" class="d-flex align-items-center w-100 px-4 py-3" style="border-bottom:1px solid #ddd; gap:1rem;">
        <!-- Per-page selector -->
        <div style="min-width:150px;">
          <select name="perpage" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="5"   {{ request('perpage')=='5'   ? 'selected':'' }}>5</option>
            <option value="10"  {{ request('perpage')=='10'  ? 'selected':'' }}>10</option>
            <option value="20"  {{ request('perpage')=='20'  ? 'selected':'' }}>20</option>
            <option value="50"  {{ request('perpage')=='50'  ? 'selected':'' }}>50</option>
            <option value="all" {{ request('perpage')=='all' ? 'selected':'' }}>All</option>
          </select>
        </div>

        <!-- Center count display -->
        <div class="flex-grow-1 text-center">
          @if($employees->total() > 0)
            Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} entries
          @else
            No entries found
          @endif
        </div>

        <!-- Search input and button -->
        <div class="d-flex align-items-center" style="min-width:300px; justify-content:flex-end;">
          <input
            type="text"
            name="search"
            id="search-bar"
            value="{{ request('search') }}"
            placeholder="Search ID, Name, Email, Role..."
            class="form-control form-control-sm me-2"
            onkeydown="if(event.key==='Enter'){ this.form.submit(); }"
          >
          <button type="submit" class="btn btn-sm btn-primary">Search</button>
        </div>
      </form>
 
      <!-- Table -->
      <div class="table-responsive p-3">
        <table class="table table-striped table-hover" id="employees-table">
          <thead class="thead-light">
            <tr>
              <th>#</th>
              <th>Employee ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              @canany(['employee.edit', 'employee.delete'])
              
                <th>Action</th>
                @elseif (Auth::user()->id == "531")
                <th>Action</th>
              @endcan
            </tr>
          </thead>
          <tbody>
            @foreach($employees as $employee)
              <tr>
                <td>{{ $employees->firstItem() + $loop->index }}</td>
                <td>{{ $employee->employee_id }}</td>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->email }}</td>
                <td>{{ optional($employee->user)->roles->pluck('name')->first() ?? 'No role assigned' }}</td>
                @if (Auth::user()->canany(['employee.edit', 'employee.delete']) || in_array(Auth::user()->id, [531, 532]))
                    <td>
                        @if (Auth::user()->can('employee.edit') || in_array(Auth::user()->id, [531, 532]))
                            <a href="{{ route('employees.edit', $employee->id) }}" class="mx-2" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Employee">
                                <i class="fas fa-user-edit text-purple"></i>
                            </a>
                        @endif
                
                      @if (Auth::user()->can('employee.delete') || in_array(Auth::user()->id, [531, 532]))
                    @if($employee->status == 1)
                        <form action="{{ route('employees.destroy', $employee->id) }}" method="POST" class="d-inline-block">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link p-0 m-0" onclick="return confirm('Are you sure?')" data-bs-toggle="tooltip" data-bs-placement="top" title="Disable Employee">
                                <i class="fas fa-trash text-danger"></i>
                            </button>
                        </form>
                    @else
                        <form action="{{ route('employees.reactivate', $employee->id) }}" method="POST" class="d-inline-block">
                            @csrf
                            <button class="btn btn-link p-0 m-0" onclick="return confirm('Are you sure?')" data-bs-toggle="tooltip" data-bs-placement="top" title="Enable Employee">
                                <i class="fas fa-recycle" style="color: green;"></i>
                            </button>
                        </form>
                    @endif
                @endif
                
                    </td>
                @endif


              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3">
        <div>
          {{ $employees->links('pagination::bootstrap-4') }}
        </div>
      </div>

    </div>
  </div>
</div>

@endsection

@section('script')
<script>
  $(document).ready(function () {
    $('#employees-table').DataTable({
      paging:     false,
      ordering:   true,
      info:       false,
      searching:  false
    });
  });
</script>
@endsection