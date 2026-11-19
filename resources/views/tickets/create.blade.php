@extends('layouts.main')
@section('breadcrumb')
<nav aria-label="breadcrumb">
  <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
    <li class="breadcrumb-item text-sm">
      <a class="text-white" href="javascript:;">
        <i class="ni ni-box-2"></i>
      </a>
    </li>
    <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Create
        Tickets</a></li>
  </ol>
  <h6 class="font-weight-bolder mb-0 text-white">Create New Tickets</h6>
</nav>
@endsection
@section('content')
<div class="row">
  <div class="col-12 mx-auto">
    <form action="{{ route('tickets_store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <!-- Section 1: Ticket Information -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between bg-primary text-white">
          <h5 class="mb-0">Ticket Information</h5>
          <button type="submit" class="btn btn-primary px-5 mb-0">Create Ticket</button>
        </div>
        <div class="card-body">
          <div class="row">
            <!-- Ticket Type -->
            <div class="form-group col-md-4">
              <label for="tickettype_id" class="form-control-label">Ticket Type</label>
              <select class="form-control" name="tickettype_id" id="tickettype_id" required>
                <option value="">Select Ticket Type</option>
                @foreach($tickettypes as $tickettype)
          @if($tickettype->name == 'HRPSP POC')
        @if(auth()->user()->hasAnyRole(['JCS', 'RDM', 'RDS',"HRPSP TEAM"]))
      <option value="{{ $tickettype->id }}">{{ $tickettype->name }}</option>
    @endif
      @else
      <option value="{{ $tickettype->id }}">{{ $tickettype->name }}</option>
    @endif
        @endforeach

              </select>
              @error('tickettype_id')
        <div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

            <!-- Ticket Subtype -->
            <div class="form-group col-md-4">
              <label for="ticketsubtype_id" class="form-control-label">Ticket Sub Type</label>
              <select class="form-control" name="ticketsubtype_id" required id="ticketsubtype_id">
                <option value="">Select Ticket Sub Type</option>
                <!-- Subtypes will be dynamically updated -->
              </select>
              @error('ticketsubtype_id')
        <div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

            <!-- Attachment -->
            <div class="form-group col-md-4">
              <label for="ticket_attachment">Attachment</label>
              <input type="file" name="ticket_attachment" class="form-control" id="ticket_attachment">
              @error('ticket_attachment')
        <div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>
          </div>


          <h5 class="mb-0 mt-3">Additional Information</h5>


          <div class="row mt-3">
            <!-- Description -->
            <div class="form-group col-md-12">
              <label for="description">Description</label>
              <textarea name="description" class="form-control" id="description" rows="5"
                placeholder="Describe the issue or request"></textarea>
              @error('description')
        <div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

          </div>
        </div>
      </div>

    </form>
  </div>
</div>
@endsection

@section('script') 
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script type="text/javascript">
  document.getElementById("mobile").addEventListener("input", function () {
    var inputValue = this.value;
    console.log(inputValue);
    if (inputValue === "" || inputValue < 923) {
      this.value = "923";
    }
    if (inputValue.length > 12) {
      this.value = inputValue.slice(0, 12);
    }
  });

</script>
<script>
  let ticketSettings = @json($ticketSettings);
  console.log(ticketSettings)
  $('#tickettype_id').on('change', function () {
    let ticketTypeId = $(this).val(); // Get selected ticket type ID
    let subtypeDropdown = $('#ticketsubtype_id');
    console.log(ticketTypeId)
    subtypeDropdown.empty();
    subtypeDropdown.append('<option value="">Select Ticket Sub Type</option>');

    let filteredSubtypes = ticketSettings.filter(function (setting) {
      return setting.ticket_type_id == ticketTypeId;
    });
    console.log(filteredSubtypes)

    filteredSubtypes.forEach(function (setting) {
      subtypeDropdown.append('<option value="' + setting.ticket_sub_type['id'] + '">' + setting.ticket_sub_type['name'] + '</option>');
    });
  });
</script>
<script>
   // If there's a "failed" message in the session, show Toastify
   @if(session('failed'))
       Toastify({
         text: "{{ session('failed') }}",
         duration: 3000,
         gravity: "top",
         position: "right",
         backgroundColor: "#f5365c", // or any color
         close: true
       }).showToast();
   @endif
   // If there's a "success" message in the session, show Toastify
   @if(session('success'))
       Toastify({
         text: "{{ session('success') }}",
         duration: 3000,
         gravity: "top",
         position: "right",
         backgroundColor: "#2dce89", // or any color
         close: true
       }).showToast();
   @endif
 </script>

@endsection