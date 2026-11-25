@extends('layouts.main')

@section('breadcrumb')
<style>
  #regions-table_filter {
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
      <a class="opacity-5 text-white" href="javascript:;">Region List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Region List</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">
      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Regions</h5>
            <p class="text-sm mb-0">List of Regions</p>
          </div>
          @can('region.create')
        <a href="{{ route('regions.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+&nbsp; Add New</a>
      @endcan
        </div>
      </div>

      @include('listings.filters', ['listingLabel' => 'Regions'])

      <!-- Table -->
      <div class="table-responsive p-3">
        <table data-serial-offset="{{ max(0, ($regions->firstItem() ?? 1) - 1) }}" class="table table-striped table-hover" id="regions-table">
          <thead class="thead-light">
            <tr>
              <th data-serial-column>Sr#</th>
              <th>Region Name</th>
              <th>Parent Region</th>
              @canany(['region.edit', 'region.delete'])
          <th>Action</th>
        @endcanany
            </tr>
          </thead>
          <tbody>
            @foreach($regions as $region)
        <tr>
              <td>{{ ($regions->firstItem() ?? 1) + $loop->index }}</td>
          <td>{{ $region->name }}</td>
          <td>{{ $region->parent ? $region->parent->name : "Main Region" }}</td>
          @canany(['region.edit', 'region.delete'])
        <td>
        <div class="d-flex align-items-center">
        @can('region.edit')
      <a href="{{ route('regions.edit', $region->id) }}" class="me-3" data-bs-toggle="tooltip"
      data-bs-placement="top" title="Edit">
      <i class="fas fa-user-edit text-purple"></i>
      </a>
    @endcan
        @can('region.delete')
      <x-forms.action-btn href="" id="table_data_delete" action="delete" title="delete region"
      data-action="{{route('regions.destroy', $region['id'])}}" data-parent="tr"></x-forms.action-btn>
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
          {{ $regions->links('pagination::bootstrap-4') }}
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
    let table = $('#regions-table').DataTable({
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
