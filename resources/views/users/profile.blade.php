@extends('layouts.main')
@section('breadcrumb')
<x-templates.bread-crumb page-title="Show User Profile">
  <li class="breadcrumb-item text-sm text-white"><a class="text-white" href="{{route('users.index')}}">Show User
      Profile</a></li>
</x-templates.bread-crumb>
@endsection
<style>
    .input-edit-disabled{
        color:#808080;
    }
    .modal-dialog{
        height:fit-content;
    }
    .modal{
        z-index:10000 !important;
    }
</style>
@section('content')
@php
  $user = auth()->user();
@endphp
<x-templates.basic-page-temp page-title="View User Profile" page-desc="View User Details">
    <div>
        
    
    <x-slot:pageHeader>
    <div class="ms-auto my-auto mt-lg-0 mt-4">
      <div class="ms-auto my-auto">
        <!-- <a href="#" class="btn bg-gradient-primary btn-sm mb-0" type="button" data-bs-toggle="modal"
          data-bs-target="#recordCreate">+&nbsp; Add</a>  -->
      </div>
    </div>
    </x-slot>

    <div class="row mx-0 container">

      <div class=" p-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
          <!-- Left side - Profile Picture and Change Photo button -->
          <div class="d-flex flex-column flex-md-row align-items-center">
            <div class="profile-image-wrapper mb-3 mb-md-0 me-md-3 text-center">
              <img
                src="{{ isset($user) && $user->image ? asset('images/profile/' . $user->image) : asset('images/profile/User.ico') }}"
                class="rounded-circle" width="100" style="width: 100px;
    height: 100px;" alt="avatar"><br>
            </div>

            <div class="text-center text-md-start">
              <h6 class="mb-1">{{ $user->name }}</h6>
              <p class="mb-2" style="font-size:12px;">{{ $user->email }}</p>

              <!-- File Upload Form -->
              <form action="{{ route('users.profileStore', $user->id) }}" method="POST" enctype="multipart/form-data"
                id="photoForm">
                @csrf
                @method('PUT')
                <!-- File Input, which is hidden -->
                <input type="file" name="image" id="profile_photo" style="display: none;"
                  onchange="document.getElementById('photoForm').submit()">

                <!-- Button to trigger the file input -->
                <button type="button" class="btn btn-primary btn-sm"
                  onclick="document.getElementById('profile_photo').click()">
                  <i class="fas fa-camera"></i> Change Photo
                </button>
              </form>
            </div>
          </div>

          <!-- Right side - User Info and Status -->
          <div class="text-center text-md-end mt-3 mt-md-0">
            <div class="">
              @if($user->roles && $user->roles->count() > 0)
          @foreach($user->roles as $role)
        <span class="badge bg-secondary">{{ $role->name }}</span>
      @endforeach
        @else
        <span class="badge bg-secondary">No Role Assigned</span>
      @endif
            </div>

            <p class="text-muted mt-2">Joined {{ $user->created_at->format('d M Y') }}</p>
          </div>
        </div>

      </div>

      <div class="col-12 ">

        <h5 class="m-0 " style="color:#007bff">General info</h5>
        <div class="row mx-0 mt-3">

          <div class="col-md-4">
            <label>Name:</label>
            <p id="name-static " class="input-custom">{{ $user->name }}

              <span type="button" class="input-edit-custom" onclick="openModal('Name', '{{ $user->name }}', 'name')">
               <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                  xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </span>
            </p>
          </div>
          <div class=" col-md-4 ">
            <label>Mobile</label>
            <p class='input-custom'>
              {{$user->mobile}}
              <span type="button" class="input-edit-custom"
                onclick="openModal('Mobile', '{{ $user->mobile }}', 'mobile')">
               <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                  xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </span>
            </p>
          </div>


          <div class="col-md-4">
            <label>Email</label>
            <p class="input-custom">
              {{$user->email}}
              <span type="button" class="input-edit-custom" onclick="openModal('Email', '{{ $user->email }}', 'email')">
               <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                  xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </span>
            </p>

          </div>


        </div>
        <div class="row mx-0 mt-0 ">

          <div class=" col-md-4">
            <label>Secondary Email (optional)</label>
            <p class="input-custom">
              {{$user->secondary_email ?? 'Secondary Email Address'}}
              <span type="button" class="input-edit-custom"
                onclick="openModal('Secondary Email', '{{ $user->secondary_email }}', 'secondary_email')">
               <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                  xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </span>
            </p>

          </div>
          <div class="form-group col-md-4">
            <label for="dob">Date of Birth</label>
            <p class="input-custom">
              {{ $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d M Y') : 'N/A' }}

              <span type="button" class="input-edit-custom" onclick="openModal2('dobModal')">
                <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor" stroke-linecap="round"
                  stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                  xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </span>
            </p>
          </div>

          <div class=" col-md-4">
            <label>Gender</label>
            <p class="input-custom">
              <span class="input-text-custom">
                {{$user->gender ?? 'N/A'}}

              </span>
              <span type="button" class="input-edit-custom"
                onclick="openDropdownEditModal('Gender', 'gender', [{id: 'male', name: 'Male'}, {id: 'female', name: 'Female'}], '{{ $user->gender }}', '{{ route('users.profileStore', $user->id) }}')">
                <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor" stroke-linecap="round"
                  stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                  xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </span>
            </p>

          </div>
        </div>
      </div>

      <h5 class="m-0 mt-5 " style="color:#007bff">Company info</h5>

      <div class="row mx-0 mt-3">
        <div class="col-md-4">
          <label>Designation:</label>
          <p class="input-custom">
            {{ $user->designation->name ?? 'No Designation Assigned' }}
            <span type="button" class="input-edit-disabled">
              <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
              </svg>
            </span>
          </p>
        </div>
        <div class="col-md-4">
          <label>Department:</label>
          <p class="input-custom">
            {{ $user->department->name ?? 'No Department Assigned' }}
            <span type="button" class="input-edit-disabled">
              <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" width="20"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
              </svg>
            </span>
          </p>
        </div>
        <div class="col-md-4">
  @php
    // 1) Find this user’s Employee record
    $emp = \App\Models\Employee::where('user_id', $user->id)->first();

    // 2) If it exists and has a report_to value, fetch that manager’s name
    $mgrName = null;
    if ($emp && $emp->report_to) {
      $mgrName = \App\Models\Employee::where('employee_id', $emp->report_to)
                                     ->value('name');
    }
  @endphp

  <label>Report To:</label>
  <p class="input-custom">
    {{ $mgrName ?? 'No Manager Assigned' }}
    <span type="button" class="input-edit-disabled ">
      <!-- your SVG icon -->
      <svg class="feather feather-edit" fill="none" height="24" stroke="currentColor"
           stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
           viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
      </svg>
    </span>
  </p>
</div>

     


      </div>
      
      <h5 class="m-0 mt-5 " style="color:#007bff">Change Password</h5>

      <div class="row mx-0 mt-3 mb-5">

        <div class="col-md-4">
          <label>New Password</label>
          <p class="input-custom p-0">
            <input type="password" class="form-control" id="new_password" placeholder="Enter New Password"
              style="border:none;padding: 6px 10px;">
          </p>
        </div>

        <div class="col-md-4">
          <label>Confirm Password</label>
          <p class="input-custom p-0">
            <input type="password" class="form-control" id="confirm_password" placeholder="Confirm Password"
              style="border:none;padding: 6px 10px;">
          </p>
        </div>

        <div class="col-md-12 mt-3">
          <button class="btn btn-primary" style="font-size:10px" onclick="updatePassword()">Update Password</button>
        </div>

        <!-- Form to submit the password -->
        <form action="{{ route('users.profileStore', $user->id) }}" method="POST" id="updatePasswordForm"
          style="display:none;">
          @csrf
          @method('PUT')
          <input type="hidden" name="password" id="password_field">
        </form>
      </div>
        

<h5 class="m-0 mt-5" style="color:#007bff;text-align:center">Login with QR Code</h5>

<div class="row mx-0 ">
  <div class="col-12">
    <div class="qr-card p-3 p-md-4">
      <div class="d-flex ">

       {{-- One-liner QR banner --}}
@php
    // Correct base URL (don't use rtrim with a string mask)
    $baseUrl = rtrim(config('app.url'), '/');

    // 10-min encrypted token
    $token = encrypt($user->id.'|'.now()->addMinutes(10));

    // What the scanner should read (pure JSON string)
    $qrData = [
        
        'email'    => $user->email,
        'token'    => $token,
        'redirect_url'     =>$baseUrl,
        // optional extras your app can use:
        // 'api'      => url('/api/auth/qr-login'),
        // 'deeplink' => 'myflexihr://qr-auth?email='.rawurlencode($user->email).'&token='.rawurlencode($token),
    ];
    $qrJson = json_encode($qrData, JSON_UNESCAPED_SLASHES);

    // Generate SVG QR from JSON (no Imagick/GD)
    $svg = QrCode::format('svg')->size(260)->margin(0)->generate($qrJson);
@endphp

{{-- center the QR --}}
<div class="d-flex justify-content-center  w-100">
  <div class="qr-banner d-flex flex-column align-items-center justify-content-center mx-auto">
    <span class="dot me-2"></span>
    <div class="qr-glow">{!! $svg !!}</div>
      <p class="text-muted mb-0 mt-2 " style="font-size:12px;letter-spacing:.2px;">
      <i class="fas fa-qrcode me-1"></i>  Scan to sign in or install if you don’t have it yet.
    </p>

  </div>
</div>



      </div>
    </div>
  </div>
</div>


    </div>

    <!-- Dynamic Modal to update any field -->
    <div class="modal fade" id="dynamicModal" tabindex="-1" aria-labelledby="dynamicModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="dynamicModalLabel">Edit Field</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form action="{{ route('users.profileStore', $user->id) }}" method="POST" id="dynamicForm">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label for="dynamicInput" id="dynamicLabel"></label>
                <input type="text" name="dynamicInput" class="form-control" id="dynamicInput" required>
              </div>
              <button class="btn bg-gradient-primary mb-0 btn-ladda" type="submit" title="Send"
                data-style="expand-left">Update</button>
            </form>
          </div>

        </div>
      </div>
    </div>

    <div class="modal fade" id="editDropdownModal" tabindex="-1" aria-labelledby="editDropdownModalLabel"
      aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editDropdownModalLabel">Edit Field</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form action="" method="POST" id="editDropdownForm">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label id="editDropdownLabel" for="editDropdownSelect">Select Option</label>
                <select class="form-control" name="editDropdownField" id="editDropdownSelect" required>
                  <!-- Options will be populated dynamically -->
                </select>
                <button class="btn bg-gradient-primary mt-3 mb-0" type="submit" title="Send"
                  data-style="expand-left">Update</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- Modal to update Date of Birth -->
    <div class="modal fade" id="dobModal" tabindex="-1" aria-labelledby="dobModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="dobModalLabel">Edit Date of Birth</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Form to update DOB -->
            <form action="{{ route('users.profileStore', $user->id) }}" method="POST" id="dobForm">
              @csrf
              @method('PUT')
              <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input type="date" name="dob" class="form-control" id="dob" value="{{ $user->dob }}" required>
              </div>
              <button class="btn bg-gradient-primary mt-3 mb-0" type="submit" title="Update Date of Birth">
                Update
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
</div>
</x-templates.basic-page-temp>
@endsection

@section('script')
<script>
  function updatePassword() {
    var newPassword = document.getElementById('new_password').value;
    var confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword === '' || confirmPassword === '') {
      AppMessages.show({ type: 'error', message: 'Please fill out both password fields.' });
      return;
    }

    if (newPassword.length < 8) {
      AppMessages.show({ type: 'error', message: 'Password must be at least 8 characters long.' });
      return;
    }

    var upperCasePattern = /[A-Z]/;
    var numberPattern = /[0-9]/;
    var specialCharacterPattern = /[!@#$%^&*(),.?":{}|<>]/;

    if (!upperCasePattern.test(newPassword)) {
      AppMessages.show({ type: 'error', message: 'Password must contain at least one uppercase letter.' });
      return;
    }

    if (!numberPattern.test(newPassword)) {
      AppMessages.show({ type: 'error', message: 'Password must contain at least one number.' });
      return;
    }

    if (!specialCharacterPattern.test(newPassword)) {
      AppMessages.show({ type: 'error', message: 'Password must contain at least one special character.' });
      return;
    }

    if (newPassword !== confirmPassword) {
      AppMessages.show({ type: 'error', message: 'Passwords do not match!' });
      return;
    }

    document.getElementById('password_field').value = newPassword;

    document.getElementById('updatePasswordForm').submit();
  }

  function openDropdownEditModal(fieldLabel, fieldName, options, currentValue, actionUrl) {
    // Set modal title
    document.getElementById('editDropdownModalLabel').innerText = `Edit ${fieldLabel}`;

    // Set form action URL
    document.getElementById('editDropdownForm').action = actionUrl;

    // Set select field name
    document.getElementById('editDropdownSelect').name = fieldName;

    // Set label
    document.getElementById('editDropdownLabel').innerText = fieldLabel;

    // Populate select options dynamically
    const selectElement = document.getElementById('editDropdownSelect');
    selectElement.innerHTML = ''; // Clear existing options
    options.forEach(option => {
      const isSelected = option.id == currentValue ? 'selected' : '';
      selectElement.innerHTML += `<option value="${option.id}" ${isSelected}>${option.name}</option>`;
    });

    // Show the modal
    $('#editDropdownModal').modal('show');
  }

  function openModal2(modalId) {
    $('#' + modalId).modal('show');
  }

  function openModal(fieldLabel, fieldValue, fieldName) {
    // Set the modal title and label
    document.getElementById('dynamicModalLabel').innerText = `Edit ${fieldLabel}`;
    document.getElementById('dynamicLabel').innerText = fieldLabel;

    // Set the current value in the input field
    document.getElementById('dynamicInput').value = fieldValue;

    // Update the input name to match the field name (so the server knows what to update)
    document.getElementById('dynamicInput').setAttribute('name', fieldName);

    // Show the modal
    $('#dynamicModal').modal('show');
  }

</script>
@endsection