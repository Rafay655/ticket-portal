@extends('layouts.main')
@section('breadcrumb')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
      <li class="breadcrumb-item text-sm">
        <a class="text-white" href="javascript:;">
          <i class="ni ni-box-2"></i>
        </a>
      </li>
      <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Edit User</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Edit User</h6>
  </nav>
@endsection
@section('content')
<div class="row">
  <div class="col-12 mx-auto">
    <form action="{{ route('users.update', $user->id) }}" method="POST" enctype="multipart/form-data">
      @method('PUT')
      @csrf
      
      <!-- Section 1: User Details -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between bg-primary text-white">
          <h5 class="mb-0">User Details</h5>
          <button type="submit" class="btn btn-primary px-5 mb-0">Update</button>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="form-group col-md-3">
              <label for="user_id">User ID</label>
              <input type="text" name="user_id" class="form-control" id="user_id" value="{{ $user->user_id }}" placeholder="User ID">
              @error('user_id')<div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group col-md-3">
              <label for="name">Name</label>
              <input type="text" name="name" class="form-control" id="name" value="{{ $user->name }}" placeholder="Name">
              @error('name')<div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group col-md-3">
              <label for="designation_id">Designation</label>
              <select class="form-control" name="designation_id" id="designation_id">
                @foreach($designations as $designation)
                  <option {{ $user->designation_id == $designation->id ? "selected" : "" }} value="{{ $designation->id }}">{{ $designation->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group col-md-3">
              <label for="department_id">Department</label>
              <select class="form-control" name="department_id" id="department_id">
                @foreach($departments as $department)
                  <option {{ $user->department_id == $department->id ? "selected" : "" }} value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Work Information -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Work Information</h5>
        </div>
        <div class="card-body">
          <div class="row">
            

            <div class="form-group col-md-2">
              <label for="grade">Region</label>
              <select class="form-control" name="grade" id="grade">
                @foreach($grades as $grade)
                  <option {{ $user->grade == $grade->name ? "selected" : "" }} value="{{$grade->name}}">{{ $grade->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group col-md-3">
              <label for="region_id">Region</label>
              <select class="form-control" name="region_id" id="region_id">
                @foreach($regions as $region)
                  <option {{ $user->region_id == $region->id ? "selected" : "" }} value="{{ $region->id }}">{{ $region->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group col-md-3">
              <label for="sub_region_id">Sub-Region</label>
              <select class="form-control" name="sub_region_id" id="sub_region_id">
                @foreach($sub_regions as $sub_region)
                  <option {{ $user->sub_region_id == $sub_region->id ? "selected" : "" }} value="{{ $sub_region->id }}">{{ $sub_region->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group col-md-2">
              <label for="roles">Role</label>
              <select class="form-control" name="roles" id="roles">
                @foreach($roles as $role)
                  <option {{ $user->roles->pluck('name')->first() == $role->name ? "selected" : "" }} value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
              </select>
            </div>

            <input type="hidden" name="poc_user" value="0">
            <div class="form-group col-md-2 mt-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" value="1" id="poc_user" name="poc_user" {{ $user->poc_user == 1 ? "checked" : "" }}>
                <label class="form-control-label" for="poc_user">POC User</label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Contact Information & Profile Picture -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">Contact Information & Profile Picture</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="form-group col-md-3">
              <label for="email">Email</label>
              <input type="text" name="email" class="form-control" id="email" value="{{ $user->email }}" placeholder="Email Address">
              @error('email')<div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group col-md-3">
              <label for="secondary_email">Secondary Email (optional)</label>
              <input type="text" name="secondary_email" class="form-control" id="secondary_email" value="{{ $user->secondary_email }}" placeholder="Secondary Email Address">
              @error('secondary_email')<div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group col-md-3">
              <label for="mobile">Mobile</label>
              <input type="number" name="mobile" class="form-control" id="mobile" value="{{ $user->mobile }}" placeholder="Mobile Number">
              @error('mobile')<div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>

            <div class="form-group col-md-3">
              <label for="profile_picture">Profile Picture</label>
              <input type="file" name="image" class="form-control" id="profile_picture">
              @error('profile_picture')<div class="alert alert-danger">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>

    </form>
  </div>
</div>
@endsection


@section('script') 

    <script type="text/javascript">
        document.getElementById("mobile").addEventListener("input", function() {
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

<!--   <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script> -->

@endsection