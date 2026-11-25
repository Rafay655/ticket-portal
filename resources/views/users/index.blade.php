@extends('layouts.main')
@section('breadcrumb')
<style>
  #users-table_filter {
    display: none !important
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
      <a class="opacity-5 text-white" href="javascript:;">Users List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Users List</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">
      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Users</h5>
            <p class="text-sm mb-0">List of Users</p>
          </div>
          <div>

            @can('user.create')
            <a href="{{ route('users.create') }}" class="btn bg-purple btn-sm mb-0">+&nbsp; Add New</a>
            @endcan
            @can(['admindashboard.index', 'user.delete'])
            <a href="{{ route('user.separation.index') }}" class="btn bg-purple btn-sm mb-0">+&nbsp; Disable users</a>
            @endcan
          </div>
        </div>
      </div>

      @include('listings.filters', ['listingLabel' => 'Users'])

      <!-- Table -->
      <div class="table-responsive p-3">
        <table data-serial-offset="{{ max(0, ($users->firstItem() ?? 1) - 1) }}" class="table table-striped table-hover" id="users-table">
          <thead class="thead-light">
            <tr>
              <th data-serial-column>Sr#</th>
              <th>User ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              @canany(['user.edit', 'user.delete'])
              <th>Action</th>
              @endcan
            </tr>
          </thead>
          <tbody>
            @foreach($users as $user)
            <tr>
              <td>{{ ($users->firstItem() ?? 1) + $loop->index }}</td>
              <td>{{ $user->user_id }}</td>
              <td>{{ $user->name }}</td>
              <td>{{ $user->email }}</td>
              <td>{{ $user->roles->pluck('name')->first() }}</td>
              @canany(['user.edit', 'user.delete'])
              <td class="d-flex align-items-center">
                @can('user.edit')
          <a href="{{ route('users.edit', $user->id) }}" class="mx-2" data-bs-toggle="tooltip"
            data-bs-placement="top" title="Edit User">
            <i class="fas fa-user-edit text-purple"></i>
          </a>
        @endcan
                @can('user.delete')
          @if ($user->status == 1)

        <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline-block">
        @csrf
        @method('DELETE')
        <button class="btn btn-link p-0 m-0" data-confirm-message="Are you sure?"
        data-bs-toggle="tooltip" data-bs-placement="top" title="Disable User">
        <i class="fas fa-trash text-danger"></i>
        </button>
        </form>

      @else
      <form action="{{ route('users.reactivate', $user->id) }}" method="POST" style="display: inline;">
      @csrf

      <button class="btn btn-link p-0 m-0" data-confirm-message="Are you sure?"
      data-bs-toggle="tooltip" data-bs-placement="top" title="Enable User">
      <!-- Font Awesome Icon with Tooltip -->
      <i class="fas fa-recycle" style="color: green;"></i>





      </button>
      </form>
    @endif
        @endcan
              </td>
              @endcan
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3">
        <div>
          {{ $users->links('pagination::bootstrap-4') }}
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
    let table = $('#users-table').DataTable({
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
