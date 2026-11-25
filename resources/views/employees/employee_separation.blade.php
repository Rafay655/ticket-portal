@extends('layouts.main')

@section('breadcrumb')
<style>
  #employee-separation-table_filter {
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
      <a class="opacity-5 text-white" href="javascript:;">Employee Separation</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Employee Separation</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">
      <div class="card-header bg-white-custom pb-0">
        <div>
          <h5 class="mb-0">Employee Separation</h5>
          <p class="text-sm mb-0">List of Separated Employees</p>
        </div>
      </div>

      @include('listings.filters', ['listingLabel' => 'Separated Employees'])

      <!-- Table -->
      <div class="table-responsive p-3">
        <table data-serial-offset="{{ max(0, ($employeeSeparations->firstItem() ?? 1) - 1) }}" class="table table-striped table-hover" id="employee-separation-table">
          <thead class="thead-light">
            <tr>
              <th data-serial-column>Sr#</th>
              <th>Employee ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Designation</th>
              <th>Separation Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($employeeSeparations as $employeeSeparation)
            <tr>
              <td>{{ ($employeeSeparations->firstItem() ?? 1) + $loop->index }}</td>
              <td>{{ $employeeSeparation->employee_id }}</td>
              <td>{{ $employeeSeparation->name }}</td>
              <td>{{ $employeeSeparation->email }}</td>
              <td>{{ $employeeSeparation->department->name ?? 'N/A' }}</td>
              <td>{{ $employeeSeparation->designation->name ?? 'N/A' }}</td>
              <td>{{ $employeeSeparation->separation_date }}</td>
              <td>{{ $employeeSeparation->status == 0 ? 'Inactive' : 'Active' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3">
        <div>
          {{ $employeeSeparations->links('pagination::bootstrap-4') }}
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('script')
<script>
  $(document).ready(function () {
    // Enable DataTables
    let table = $('#employee-separation-table').DataTable({
      serverSide: false,
      searching: false,
      order: [],
      columnDefs: [{ targets: 0, orderable: false, searchable: false }],
      paging: false, // Disable DataTables pagination to use Laravel's
      ordering: true, // Enable column sorting
      info: false // Disable DataTables info to use Laravel's pagination
    });

    // Add search functionality to the custom search bar
  });
</script>
@endsection
