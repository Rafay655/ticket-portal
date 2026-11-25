@extends('layouts.main')
@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
      <li class="breadcrumb-item text-sm">
        <a class="text-white" href="javascript:;">
          <i class="ni ni-box-2"></i>
        </a>
      </li>
      <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Create Ticket Category</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Create New Ticket Category</h6>
  </nav>
@endsection

@section('content')
<div class="row">
  <div class="col-12 mx-auto">
    <form action="{{ route('ticketcategories.store') }}" method="POST">
      @csrf
      
      @include('ticketcategories.partials.form')
    </form>
  </div>
</div>
@endsection
