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
            @include('tickets.partials.hierarchy-fields', ['filterPocTypes' => true])

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
                placeholder="Describe the issue or request">{{ old('description') }}</textarea>
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
