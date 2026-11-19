@extends('layouts.main')

@section('breadcrumb')
<style>
  #roles-table_filter {
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
    <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Roles
        List</a></li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Roles List</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">

      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Roles</h5>
            <p class="text-sm mb-0">List of Roles</p>
          </div>
          @can('role.create')
        <a href="{{ route('roles.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+&nbsp; Add New</a>
      @endcan
        </div>
      </div>

      <!-- Controls Row -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3" style="border-bottom: 1px solid #ddd;">
        <!-- Per Page Dropdown -->
        <form method="GET" action="{{ route('roles.index') }}" style="width:200px;" class="d-flex align-items-center">
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
          <input type="text" id="search-bar" placeholder="Search Roles..." class="form-control form-control-sm">
        </div>
      </div>

      <!-- Table -->
      <div class="table-responsive p-3">
        <table class="table table-striped table-hover" id="roles-table">
          <thead class="thead-light">
            <tr>
              <th>S.No</th>
              <th>Role Name</th>
              @canany(['role.edit', 'role.delete'])
              <th>Action</th>
              @endcan
            </tr>
          </thead>
          <tbody>
          @php
          $userRole = Auth::user()->roles->pluck('name')->first(); // Assuming the user has a single role
        @endphp
            @foreach($roles as $index => $role)
            @if(($userRole == 'superAdmin') || 
            ($userRole != 'superAdmin' && $role->name != 'superAdmin' && $role->name != 'admin'))
            <tr>
              <td>{{ $roles->firstItem() + $index }}</td>
              <td>{{ $role->name }}</td>
              @canany(['role.edit', 'role.delete'])
              <td class="text-sm">
                @can('role.edit')
          <x-forms.action-btn href="{{route('roles.show', $role['id'])}}" action="edit"
            title="edit role"></x-forms.action-btn>
        @endcan
                @can('role.delete')
          <x-forms.action-btn href="" id="table_data_delete" action="delete" title="delete role"
            data-action="{{route('roles.destroy', $role['id'])}}" data-parent="tr"></x-forms.action-btn>
        @endcan
              </td>
              @endcan
            </tr>
            @endif
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3">
        <div>
          {{ $roles->links('pagination::bootstrap-4') }}
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
    let table = $('#roles-table').DataTable({
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