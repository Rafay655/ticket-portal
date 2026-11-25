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
            <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Ticket
                    Report</a></li>
        </ol>
        <h6 class="font-weight-bolder mb-0 text-white">Ticket Report</h6>
    </nav>
    @endsection
    @section('content')
    <div class="tr-job-posted section-padding">
        <div class="container">
            <h2 class="text-white mb-5">Available Report</h2>
            <div class="job-tab text-center">
                <ul class="nav nav-tabs justify-content-center" role="tablist">
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs1 active" href="#tab1" aria-controls="tab1" role="tab"
                            data-toggle="tab" aria-selected="true">Ticket Detail </a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs2" href="#tab2" aria-controls="tab2" role="tab"
                            data-toggle="tab" aria-selected="false">Ticket Summary </a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs3" href="#tab3" aria-controls="tab3" role="tab"
                            data-toggle="tab" aria-selected="false">Ticket Assignment </a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs4" href="#tab4" aria-controls="tab4" role="tab"
                            data-toggle="tab" aria-selected="false">Ticket Status </a>
                    </li>
                    <li role="presentation" class="nav-item">
                        <a class="nav-link report-tab report-tabs5" href="#tab5" aria-controls="tab5" role="tab"
                            data-toggle="tab" aria-selected="false">Happiness Rating </a>
                    </li>
                </ul>

                <div class="tab-content text-left ">
                    <div role="tabpanel" class="tab-pane fade show active" id="tab1">
                        @include('report.partials.tab1')
                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="tab2">
                        <div class="row">
                            @include('report.partials.tab2')
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="tab3">
                        <div class="row">
                            @include('report.partials.tab3')
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="tab4">
                        <div class="row">
                            @include('report.partials.tab4')
                        </div>
                    </div>
                    <div role="tabpanel" class="tab-pane fade" id="tab5">
                        <div class="row">
                            @include('report.partials.tab5')
                        </div>
                    </div>
                </div>
                <div class="d-flex col-md-4 flex-column justify-content-start">
                    <div class="form-group">
                        <label for="customSelect" style="text-align:start;display:block;font-size:14px">File Type</label>
                        <select class="form-control custom-select" style="font-size:12px" id="filetype">
                            <option selected disabled value="">Select one...</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">Pdf </option>

                        </select>
                    </div>

                    <div class="d-inline text-start mt-4">
                        <button onclick="generateReport()" class="btn btn-success" id="generateBtn">Generate report</button>

                        <div class="container1-unique" style="display:none">
                            <div class="bar-unique">
                                <div class="text-center-unique ">Generating Report...</div>
                            </div>
                        </div>


                        <a href="{{ route('report.excel') }}" id="excelfile" style="display: none;"></a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <iframe id="pdfIframe"></iframe>
    @endsection
    @endcan
    
    @section('script')
    <script>
        
        var generateBtn = document.getElementById("generateBtn");
        var container = document.querySelector(".container1-unique");
    var activeTabNumber = 1;
    var errorMessage = ''
    $(document).ready(function () {
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            var href = $(e.target).attr('href');

            if (href === '#tab1') {
                activeTabNumber = 1;
            } else if (href === '#tab2') {
                activeTabNumber = 2;
            } else if (href === '#tab3') {
                activeTabNumber = 3;
            } else if (href === '#tab4') {
                activeTabNumber = 4;
            } else if (href === '#tab5') {
                activeTabNumber = 5;
            }

            console.log("Active Tab Number: " + activeTabNumber);
        });
    });
    function generateReport() {
        var e = document.getElementById("filetype");
        var value = e.value;
        if (value != 'pdf' && value != 'excel') {
            AppMessages.show({ type: 'error', message: "Kindly select file type" });
            return;
        }
        var filters = getFiltersForActiveTab();
        if (filters == 0) {
            AppMessages.show({ type: 'error', message: errorMessage });
            return;
        }
        
        container.style.display = "flex"; // Show progress bar
        generateBtn.style.display = "none";

        setTimeout(function () {
            completeProgress(value, filters);
            container.style.display = "none"; // Hide progress bar
            generateBtn.style.display = "block"; // Show the button back
        }, 3000); // Mocking the process time
    }

    function getFiltersForActiveTab() {
        var filters = {};

        if (activeTabNumber === 1) {
            var status = document.getElementById("customSelect").value;
            var startDate = document.getElementById("startDate").value;
            var endDate = document.getElementById("endDate").value;
            if (status == '') {
                errorMessage = "Status is required."
                return 0
            }
            if (!startDate) {
                errorMessage = "Start Date is required."
                return 0
            }
            if (!endDate) {
                errorMessage = "End Date is required."
                return 0
            }
            if (startDate > endDate) {
                errorMessage = "Start Date should be less than End Date."
                return 0
            }

            filters = { status, startDate, endDate };
        } else if (activeTabNumber === 2) {
            var startDate = document.getElementById("startDate1").value;
            var endDate = document.getElementById("endDate1").value;

            if (!startDate) {
                errorMessage = "Start Date is required."
                return 0
            }
            if (!endDate) {
                errorMessage = "End Date is required."
                return 0
            }
            if (startDate > endDate) {
                errorMessage = "Start Date should be less than End Date."
                return 0
            }

            filters = { startDate, endDate };
        }
        else if (activeTabNumber === 3) {
            var userField = document.getElementById("customSelect2");
            var user = userField.value;
            var userName = userField.options[userField.selectedIndex].text;
            var startDate = document.getElementById("startDate2").value;
            var endDate = document.getElementById("endDate2").value;
            if (user == '') {
                errorMessage = "User is required."
                return 0
            }
            if (!startDate) {
                errorMessage = "Start Date is required."
                return 0
            }
            if (!endDate) {
                errorMessage = "End Date is required."
                return 0
            }
            if (startDate > endDate) {
                errorMessage = "Start Date should be less than End Date."
                return 0
            }

            filters = { user, startDate, endDate, userName };
        }
        else if (activeTabNumber === 4) {

            var startDate = document.getElementById("startDate3").value;
            var endDate = document.getElementById("endDate3").value;

            if (!startDate) {
                errorMessage = "Start Date is required."
                return 0
            }
            if (!endDate) {
                errorMessage = "End Date is required."
                return 0
            }
            if (startDate > endDate) {
                errorMessage = "Start Date should be less than End Date."
                return 0
            }

            filters = { startDate, endDate };
        }
        else if (activeTabNumber === 5) {
            var userField = document.getElementById("customSelect4");
            var user = userField.value;
            var userName = userField.options[userField.selectedIndex].text;
            var startDate = document.getElementById("startDate4").value;
            var endDate = document.getElementById("endDate4").value;
            if (user=='') {
                errorMessage = "User is required."
                return 0
            }
            if (!startDate) {
                errorMessage = "Start Date is required."
                return 0
            }
            if (!endDate) {
                errorMessage = "End Date is required."
                return 0
            }
            if (startDate > endDate) {
                errorMessage = "Start Date should be less than End Date."
                return 0
            }

            filters ={ user, startDate, endDate, userName };
        }

        return filters;
    }
    function completeProgress(fileType, filters) {
        setTimeout(function () {
            if (fileType === 'pdf') {
                openPDF(filters);
            } else if (fileType === 'excel') {
                openExcel(filters);
            }

        }, 100);

    }


    function openPDF(filters) {
        var pdfUrl = '{{ route('report.pdf1') }}' + '?tab=' + activeTabNumber + '&' + new URLSearchParams(filters).toString();
        var newWindow = window.open(pdfUrl, '_blank');
        newWindow.onload = function () {
            console.log("PDF file opened");
        };
        generateBtn.style.display = "block";
        container.style.display ='none'
    }
    
    function openExcel(filters) {
        var excelUrl = '{{ route('report.excel') }}' + '?tab=' + activeTabNumber + '&' + new URLSearchParams(filters).toString();
        var downloadLink = document.getElementById('excelfile');
        downloadLink.href = excelUrl;
        downloadLink.click();
        generateBtn.style.display = "block";
        container.style.display ='none'
    }

</script>



@endsection