@extends('layouts.main')
@section('breadcrumb')
<style>
  #ticketcategories-table_filter {
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
      <a class="opacity-5 text-white" href="javascript:;">Ticket Category List</a>
    </li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Ticket Category List</h6>
</nav>
@endsection

@section('content')

<div class="row">
  <div class="col-12">
    <div class="card bg-white-custom lightborder-unique shadow-lg">

      <div class="card-header bg-white-custom pb-0">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Ticket Categories</h5>
            <p class="text-sm mb-0">List of Ticket Categories</p>
          </div>
          @can('ticketCategory.create')
          <a href="{{ route('ticketcategories.create') }}" class="btn bg-gradient-primary btn-sm mb-0">+&nbsp; Add New</a>
          @endcan
        </div>
      </div>

      @include('listings.filters', ['listingLabel' => 'Ticket Categories'])

      <!-- Table -->
      <div class="table-responsive p-3">
        <table data-serial-offset="{{ max(0, ($categories->firstItem() ?? 1) - 1) }}" class="table table-striped table-hover" id="ticketcategories-table">
          <thead class="thead-light">
            <tr>
              <th data-serial-column>Sr#</th>
              <th>Name</th>
              <th>Code</th>
              <th>Ticket Type</th>
              <th>Created At</th>
              <th>Updated At</th>
              @canany(['ticketCategory.edit', 'ticketCategory.delete'])
              <th>Action</th>
              @endcanany
            </tr>
          </thead>
          <tbody>
            @forelse($categories as $category)
            <tr>
              <td>{{ ($categories->firstItem() ?? 1) + $loop->index }}</td>
              <td>{{ $category->name }}</td>
              <td>{{ $category->code }}</td>
              <td>{{ $category->ticketType?->name ?? 'Not assigned' }}</td>
              <td>{{ $category->created_at?->format('d M Y, h:i A') ?? '-' }}</td>
              <td>{{ $category->updated_at?->format('d M Y, h:i A') ?? '-' }}</td>
              @canany(['ticketCategory.edit', 'ticketCategory.delete'])
              <td>
                <div class="d-flex align-items-center">
                  @can('ticketCategory.edit')
                  <a href="{{ route('ticketcategories.edit', $category->id) }}" class="me-3" data-bs-toggle="tooltip" data-bs-placement="top" title="Edit">
                    <i class="fas fa-user-edit text-purple"></i>
                  </a>
                  @endcan
                  @can('ticketCategory.delete')
                  <x-forms.action-btn href="" id="table_data_delete" action="delete"
                                                title="delete ticket category"
                                                data-action="{{route('ticketcategories.destroy', $category['id'])}}"
                                                data-parent="tr"></x-forms.action-btn>
                  @endcan
                </div>
              </td>
              @endcanany
            </tr>
            @empty
            <tr><td colspan="{{ auth()->user()->canAny(['ticketCategory.edit', 'ticketCategory.delete']) ? 7 : 6 }}" class="text-center text-muted py-4">No ticket categories found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="d-flex justify-content-between align-items-center px-4 py-3">
        <div>
          {{ $categories->links('pagination::bootstrap-4') }}
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('style')
<link href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap.css" rel="stylesheet" />
@endsection
