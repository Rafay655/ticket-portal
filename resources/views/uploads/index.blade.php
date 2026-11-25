@extends('layouts.main')
@can('report.index')

    @section('breadcrumb')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm">
                <a class="text-white" href="javascript:;">
                    <i class="ni ni-box-2"></i>
                </a>
            </li>
            <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">
                    Uploads </a></li>
        </ol>
        <h6 class="font-weight-bolder mb-0 text-white">Uploads</h6>
    </nav>
    @endsection

    @section('content')
    <div class="tr-job-posted section-padding">
        
        
        <div class="container">
    <div class="d-flex justify-content-between align-items-center">
            <h2 class="text-white ">Uploads</h2>
                <a href="{{ route('employees.export') }}" class="btn btn-primary">
    Export Employees to Excel
</a>
</div>
        
           

            <div class="job-tab text-center">
                <ul class="nav nav-tabs justify-content-center" role="tablist">
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs1 active ms-2" href="#tab1" aria-controls="tab1" role="tab"
                            data-toggle="tab" aria-selected="true">User uploads </a>

                    </li>
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs2" href="#tab2" aria-controls="tab2" role="tab"
                            data-toggle="tab" aria-selected="false">Ticket uploads</a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs3" href="#tab3" aria-controls="tab3" role="tab"
                            data-toggle="tab" aria-selected="false">Employee uploads</a>
                    </li>

                </ul>
                <div class="tab-content text-left ">
                    <div role="tabpanel" class="tab-pane fade show active" id="tab1">
                        <div class="row">
                            @include('uploads.user');
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="tab2">
                        <div class="row">
                            @include('uploads.ticket');
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="tab3">
                        <div class="row">
                            @include('uploads.employee');
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @endsection
@endcan