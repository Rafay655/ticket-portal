@extends('layouts.main')

@section('breadcrumb')
<style>
    #departments-table_filter {
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
        <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white"
                href="javascript:;">Department List</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Department List</h6>
</nav>
@endsection

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card bg-white-custom lightborder-unique shadow-lg">
            <div class="card-header bg-white-custom pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Departments</h5>
                        <p class="text-sm mb-0">List of Departments</p>
                    </div>
                    <div class="ms-auto my-auto mt-lg-0 mt-4">
                        @can('department.create')
                            <a href="{{ route('departments.create') }}" class="btn bg-purple btn-sm mb-0">+&nbsp; Add
                                New</a>
                        @endcan
                    </div>
                </div>
            </div>

            @include('listings.filters', ['listingLabel' => 'Departments'])

      <!-- Table -->
            <div class="table-responsive p-3">
                <table data-serial-offset="{{ max(0, ($departments->firstItem() ?? 1) - 1) }}" class="table table-striped table-hover" id="departments-table">
                    <thead class="thead-light">
                        <tr>
                            <th data-serial-column>Sr#</th>
                            <th>Name</th>
                            @canany(['department.edit', 'department.delete'])
                                <th>Action</th>
                            @endcanany
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departments as $index => $department)
                            <tr>
                                <td>{{ $departments->firstItem() + $index }}</td>
                                <td>{{ $department->name }}</td>
                                @canany(['department.edit', 'department.delete'])
                                    <td class="text-sm">
                                        @can('department.edit')
                                            <x-forms.action-btn href="{{route('departments.edit', $department['id'])}}" action="edit"
                                                title="edit department"></x-forms.action-btn>
                                        @endcan


                                        @can('department.delete')
                                            <x-forms.action-btn href="" id="table_data_delete" action="delete"
                                                title="delete department"
                                                data-action="{{route('departments.destroy', $department['id'])}}"
                                                data-parent="tr"></x-forms.action-btn>
                                        @endcan
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
                    {{ $departments->links('pagination::bootstrap-4') }}
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
        let table = $('#departments-table').DataTable({
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
