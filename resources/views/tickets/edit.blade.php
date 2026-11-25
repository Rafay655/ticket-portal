@extends('layouts.main')
@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
      <li class="breadcrumb-item text-sm">
        <a class="text-white" href="javascript:;">
          <i class="ni ni-box-2"></i>
        </a>
      </li>
      <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Edit Ticket</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Edit Ticket</h6>
  </nav>
@endsection

@section('content')
<div class="row">
  <div class="col-12 mx-auto">
    <form action="{{ route('tickets.update',$ticket->id) }}" method="post" enctype="multipart/form-data">
      @method('PUT')
      @csrf
                        <input type="hidden" name="return_to" value="{{ request('return_to') }}">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="row">

                  @include('tickets.partials.hierarchy-fields', [
                      'selectedType' => $ticket->tickettype_id,
                      'selectedCategory' => $ticket->ticket_category_id ?? $ticket->ticketsubtype?->ticket_category_id,
                      'selectedSubCategory' => $ticket->ticketsubtype_id,
                  ])

                  <div class="form-group col-md-4 @error('ticket_attachment') is-invalid @enderror">
                    <label for="ticket_attachment">Attachment</label>
                    <input type="file" name="ticket_attachment" class="form-control" id="ticket_attachment">
                    @error('ticket_attachment')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="form-group col-md-6">
                    <label for="name">Description</label>
                    <textarea  name="description" class="form-control" id="description">{{$ticket->description}}</textarea>
                  </div>

                  <div class="form-group col-md-4">
                      <button type="submit" class="btn btn-primary w-50 mt-4 ">Update Ticket</button>
                  </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
