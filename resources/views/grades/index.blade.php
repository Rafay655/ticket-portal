@extends('layouts.main')

@section('breadcrumb')
<style>
  #grades-table_filter {
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
      <a class="opacity-5 text-white" href="javascript:;">Grade List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Grade List</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">
      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Grades</h5>
            <p class="text-sm mb-0">List of Grades</p>
          </div>
          @can('grade.create')
        <a href="{{ route('grades.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+&nbsp; Add New</a>
      @endcan
        </div>
      </div>

      @include('listings.filters', ['listingLabel' => 'Grades'])

      <!-- Table -->
      <div class="table-responsive p-3">
        <table data-serial-offset="{{ max(0, ($grades->firstItem() ?? 1) - 1) }}" class="table table-striped table-hover" id="grades-table">
          <thead class="thead-light">
            <tr>
              <th data-serial-column>Sr#</th>
              <th>Name</th>
              <th>Code</th>
              @canany(['grade.edit', 'grade.delete'])
          <th>Action</th>
        @endcanany
            </tr>
          </thead>
          <tbody>
            @foreach($grades as $grade)
        <tr>
              <td>{{ ($grades->firstItem() ?? 1) + $loop->index }}</td>
          <td>{{ $grade->name }}</td>
          <td>{{ $grade->code }}</td>
          @canany(['grade.edit', 'grade.delete'])
        <td>
        <div class="d-flex align-items-center">
        @can('grade.edit')
      <a href="{{ route('grades.edit', $grade->id) }}" class="me-3" data-bs-toggle="tooltip"
      data-bs-placement="top" title="Edit">
      <i class="fas fa-user-edit text-purple"></i>
      </a>
    @endcan
        @can('grade.delete')

      <x-forms.action-btn href="" id="table_data_delete" action="delete" title="delete grade"
      data-action="{{route('grades.destroy', $grade['id'])}}" data-parent="tr"></x-forms.action-btn>


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
          {{ $grades->links('pagination::bootstrap-4') }}
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
    let table = $('#grades-table').DataTable({
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
