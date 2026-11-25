@extends('layouts.main')
@section('breadcrumb')
  {{-- Tom Select assets --}}
  <style>
    .ts-wrapper {
    border: 0;
    padding: 0;
    }

    .ts-control {
    padding: 10px;
    border-radius: 8px;
    }

    .item {
    font-size: 8px
    }
  </style>

  <nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
    <li class="breadcrumb-item text-sm">
      <a class="text-white" href="javascript:;">
      <i class="ni ni-box-2"></i>
      </a>
    </li>
    <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Create
      Employee</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Create New Employee</h6>
  </nav>
@endsection
@section('content')
  <div class="row">
    <div class="col-12 mx-auto">

    <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <!-- Section 1: Personal Details -->
      <div class="card mb-4">
      <div class="card-header d-flex justify-content-between bg-primary text-white">
        <h5 class="mb-0">Employee Details</h5>

        <button type="submit" class="btn btn-primary px-5 mb-0">Create</button>
      </div>
      <div class="card-body">
        <div class="row">
        <div class="form-group col-md-1">
          <label for="prefix_selection">Prefix</label>
          <select id="prefix_selection" class="form-control" style="font-size:10px">
          <option value="HRPSP/">HRPSP</option>
          <option value="HRPSP/JZ/">HRPSP/JZ</option>
          <option value="JZ/">JZ</option>
          <option value="PMCL/">PMCL</option>
          <option value="HRPSP/RD/">HRPSP/RD</option>
          </select>
          @error('prefix_selection')
        <div class="alert alert-danger">{{ $message }}</div>
      @enderror
        </div>
        <div class="form-group col-md-2">
          <label for="employee_id">Employee ID</label>
          <input type="text" name="employee_id" id="employee_id" class="form-control" style="font-size:10px">
          @error('employee_id')
        <div class="alert alert-danger">{{ $message }}</div>
      @enderror
        </div>

        <div class="form-group col-md-3">
          <label for="name">Name</label>
          <input type="text" name="name" class="form-control" id="name" placeholder="Name">
          @error('name')
      <div class="alert alert-danger">{{ $message }}</div>@enderror
        </div>
        <div class="form-group col-md-3">
          <label for="designation_id">Designation</label>
          <select class="form-control" name="designation_id" id="designation_id">
          @foreach($designations as $designations)
        <option value="{{$designations->id}}">{{$designations->name}}</option>
      @endforeach
          </select>
        </div>
        <div class="form-group col-md-3">
          <label for="department_id">Department</label>
          <select class="form-control" name="department_id" id="department_id">
          @foreach($departments as $department)
        <option value="{{$department->id}}">{{$department->name}}</option>
      @endforeach
          </select>
        </div>
        </div>
      </div>
      </div>
      <div class="card mb-4">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Work Information</h5>
      </div>
      <div class="card-body">
        <div class="row">
        <div class="form-group col-md-2">
          <label for="grade">Grade</label>
          <select class="form-control" name="grade" id="grade">
          <option value="" selected disabled>Select one</option>
          @foreach($grades as $grade)
        <option value="{{$grade->name}}">{{$grade->name}}</option>
      @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label for="roles">Role</label>
          <select class="form-control" name="roles" id="roles">
          <option selected disabled>Select Role</option>
          @foreach($roles as $role)
        <option value="{{ $role->name }}">{{ $role->name }}</option>
      @endforeach
          </select>

          @error('roles')
        <div class="alert alert-danger">{{ $message }}</div>
      @enderror
        </div>
        <div class="form-group col-md-3">
          <label for="region_id">Region</label>
          <select id="region_id" name="region_id[]" multiple class="form-control">
          @foreach($regions as $region)
        <option value="{{ $region->id }}">{{ $region->name }}</option>
      @endforeach
          </select>
        </div>
        {{-- SUB-REGION — auto-filled, multi select --}}
        <div class="form-group col-md-3">
          <label for="sub_region_id">Sub-Region</label>
          <select id="sub_region_id" name="sub_region_id" class="form-control" disabled> {{-- stays
          disabled until at least one region is chosen --}}
          </select>
        </div>




        <div class="form-group col-md-2">
          <label for="report_to">Report To</label>
          <select name="report_to" class="form-control" id="report_to">
          <option value="" selected disabled>Select Employee</option>
          @foreach($employees as $employee)
        <option value="{{ $employee['employee_id'] }}" data-role="{{ $employee['role'] }}"
        data-region-id="{{ $employee['region_ids'] }}" data-sub-region-id="{{ $employee['sub_region_id'] }}">
        {{ $employee['name'] }} ({{ $employee['role'] }})
        </option>
      @endforeach
          </select>
          @error('report_to')
        <div class="alert alert-danger">{{ $message }}</div>
      @enderror
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
          <input type="text" name="email" class="form-control" id="email" placeholder="Email Address">
          @error('email')
        <div class="alert alert-danger">{{ $message }}</div>
      @enderror
        </div>
        <div class="form-group col-md-3">
          <label for="secondary_email">Secondary Email (optional)</label>
          <input type="text" name="secondary_email" class="form-control" id="secondary_email"
          placeholder="Secondary Email Address">
          @error('secondary_email')
        <div class="alert alert-danger">{{ $message }}</div>
      @enderror
        </div>
        <div class="form-group col-md-3">
          <label for="mobile">Mobile</label>
          <input type="number" name="mobile" class="form-control" id="mobile" placeholder="Mobile Number">
          @error('mobile')
      <div class="alert alert-danger">{{ $message }}</div>@enderror
        </div>

        <div class="form-group col-md-3">
          <label for="profile_picture">Profile Picture</label>
          <input type="file" name="image" class="form-control" id="profile_picture">
          @error('profile_picture')
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
  <script>
    document.addEventListener('DOMContentLoaded', () => {
    // ───── INITIALIZE TOM SELECT ON REGION & SUB-REGION ─────
    const regionTS = new TomSelect('#region_id', {
      plugins: ['remove_button'],
      placeholder: 'Pick one or more regions'
    });

    const subTS = new TomSelect('#sub_region_id', {
      plugins: ['remove_button'],
      placeholder: 'Pick a sub-region',
      create: false
    });
    subTS.disable(); // start disabled

    const rolesDropdown = document.getElementById('roles');
    const reportToDropdown = document.getElementById('report_to');

    // ───── DATA FROM BLADE ─────
    const allSubRegions = @json($sub_regions);
    const reportings = [
      { "JCS": "BDM", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "RDM": "BDM", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "BDM": "HOD", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "HOD": "HEAD", "disabled": false, "regionCheck": false, "subRegionCheck": false, "disableRegion": false, "disableSubregion": true },
      { "RDS": "BSE", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "Retail Activation Manager": "BSE", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "Retail Activation Executive": "Retail Activation Manager", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "BSE": "HOD", "disabled": false, "regionCheck": true, "subRegionCheck": false, "disableRegion": false, "disableSubregion": false },
      { "HEAD": "__", "disabled": true, "regionCheck": false, "subRegionCheck": false, "disableRegion": true, "disableSubregion": true },
      { "HRPSP TEAM": "POC USER", "disabled": false, "regionCheck": true, "subRegionCheck": false, "disableRegion": false, "disableSubregion": false },
      { "POC USER": "PROJECT POC", "disabled": false, "regionCheck": false, "subRegionCheck": false, "disableRegion": false, "disableSubregion": true },
      { "PROJECT POC": "HRPSP DIRECTORS", "disabled": false, "regionCheck": false, "subRegionCheck": false, "disableRegion": true, "disableSubregion": true },
      { "HRPSP DIRECTORS": "__", "disabled": true, "regionCheck": false, "subRegionCheck": false, "disableRegion": true, "disableSubregion": true }
    ];

    
    function updateSubRegions() {

      if (!subTS) {
      console.error("subTS is not initialized.");
      return;
      }

      const selectedRegions = regionTS.getValue().map(v => Number(v));

      if (selectedRegions.length === 0) {
      subTS.disable();
      return;
      }

      subTS.clearOptions();

      const seen = new Set();
      allSubRegions.forEach(sub => {
      // Add sub-regions for selected regions
      if (selectedRegions.includes(sub.p_region_id) && !seen.has(sub.id)) {
        seen.add(sub.id);
        subTS.addOption({ value: sub.id, text: sub.name });
      }
      });

      subTS.enable();
      subTS.refreshOptions(false);
    }

    function updateReportTo() {
  const selectedRole = rolesDropdown.value;
  console.log("Selected Role:", selectedRole);
  
  const cfg = reportings.find(r => r[selectedRole] !== undefined) || {};
  console.log("Config for selected role:", cfg);
  
  // Handle Region enable/disable
  if (cfg.disableRegion) {
    regionTS.disable();
    regionTS.clear();
  } else {
    regionTS.enable();
  }
  regionTS.refreshOptions(false);

  // Handle Sub-Region enable/disable
  if (cfg.disableSubregion) {
    subTS.disable();
    subTS.clear();
  } else {
    if (subTS.options.length) subTS.enable();
  }
  subTS.refreshOptions(false);

  // Handle Report To
  if (!cfg || cfg.disabled) {
    reportToDropdown.disabled = true;
    reportToDropdown.value = '';
    return;
  }

  reportToDropdown.disabled = false;
  reportToDropdown.value = ''; // Clear the selected option

  const selRegions = regionTS.getValue();
  const selSubRegion = subTS.getValue() || '';

  Array.from(reportToDropdown.options).forEach(opt => {
    if (!opt.value) return opt.style.display = 'block'; // Keep blank options visible

    const optRole = opt.dataset.role;
    const optRegIds = opt.dataset.regionId.split(',');  // Split comma-separated region IDs
    const optSubRegId = opt.dataset.subRegionId;

    console.log("Option dataset:", opt.dataset);
    console.log("Selected Sub-Region:", selSubRegion);
    
    let show = false;
    
    // Check if the role matches
    if (optRole === cfg[selectedRole]) {
      // Check region(s) match
      const regionMatch = cfg.regionCheck ? selRegions.some(region => optRegIds.includes(region.toString())) : true;

      // Check sub-region match
      const subRegionMatch = cfg.subRegionCheck ? (optSubRegId === selSubRegion) : true;

      // Only show option if both region and sub-region conditions are met
      show = regionMatch && subRegionMatch;
    }

    // Show or hide the option based on the filtering logic
    opt.style.display = show ? 'block' : 'none';
  });

  console.log('Selected Regions:', selRegions);
  console.log('Selected Sub-Region:', selSubRegion);
  console.log('Filtered Options:', Array.from(reportToDropdown.options).map(opt => opt.style.display));
}


    // ───── WIRE EVENTS ─────
    regionTS.on('change', () => {
      updateSubRegions();
      updateReportTo();
    });
    subTS.on('change', updateReportTo);
    rolesDropdown.addEventListener('change', updateReportTo);

    // ───── INITIALIZE ─────
    updateSubRegions();
    updateReportTo();
    });
  </script>

  
  <script>
  document.addEventListener("DOMContentLoaded", function () {
  const prefixSelect = document.getElementById('prefix_selection');
  const input = document.getElementById('employee_id');

  // Define all possible prefixes
  const prefixes = ["HRPSP/JZ/", "HRPSP/", "JZ/","PMCL/","HRPSP/RD/"];

  // Function to remove existing prefixes and get the numeric part
  function removeExistingPrefix(value) {
    for (let prefix of prefixes) {
      if (value.startsWith(prefix)) {
        return value.substring(prefix.length); // Return the numeric part only
      }
    }
    return value; // If no prefix, return the original value
  }

  // Function to set the prefix
  function setPrefix(newPrefix) {
    const currentValue = input.value;
    const suffix = removeExistingPrefix(currentValue); // Get the numeric part
    input.value = newPrefix + suffix; // Set the prefix with the numeric part
  }

  // Initialize with the first prefix selected
  setPrefix(prefixSelect.value);

  // Event listener for prefix change
  prefixSelect.addEventListener('change', function () {
    setPrefix(this.value);
  });

  // Prevent user from modifying the prefix part
  input.addEventListener('keydown', function (e) {
    const currentPrefix = prefixSelect.value;
    // Prevent backspace or delete if the cursor is in the prefix area
    if (
      (e.key === 'Backspace' || e.key === 'Delete') &&
      this.selectionStart <= currentPrefix.length
    ) {
      e.preventDefault();
    }
  });

  // Ensure prefix integrity on input (only numbers after the prefix)
  input.addEventListener('input', function (e) {
    const currentPrefix = prefixSelect.value;
    const numericPart = removeExistingPrefix(this.value); // Get the numeric part
    // Allow only numbers after the prefix
    this.value = currentPrefix + numericPart.replace(/[^0-9]/g, ''); // Remove non-numeric characters
  });

  // Manage caret position to prevent cursor from entering the prefix area
  input.addEventListener('click', function () {
    const currentPrefix = prefixSelect.value;
    if (this.selectionStart < currentPrefix.length) {
      this.setSelectionRange(currentPrefix.length, currentPrefix.length); // Move cursor after prefix
    }
  });

  input.addEventListener('keyup', function (e) {
    const currentPrefix = prefixSelect.value;
    if (this.selectionStart < currentPrefix.length) {
      this.setSelectionRange(currentPrefix.length, currentPrefix.length); // Keep the cursor after the prefix
    }
  });
});

  </script>

@endsection
