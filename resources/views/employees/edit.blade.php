@extends('layouts.main')
<style>
    .ts-wrapper {
    border:  0 !important;
    padding: 0 !important;
    }

    .ts-control {
    padding: 10px !important;
    border-radius: 8px !important;
    }

    .item {
    font-size: 8px !important;
    }
  </style>

@section('breadcrumb')
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
      <li class="breadcrumb-item text-sm">
        <a class="text-white" href="javascript:;">
          <i class="ni ni-box-2"></i>
        </a>
      </li>
      <li class="breadcrumb-item text-sm text-white active"><a class="opacity-5 text-white" href="javascript:;">Edit Employee</a></li>
    </ol>
    <h6 class="font-weight-bolder mb-0 text-white">Edit Employee</h6>
  </nav>
@endsection

@section('content')
  <div class="row">
    <div class="col-12 mx-auto">
      <form action="{{ route('employees.update', $employee1->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- Section 1: Personal Details -->
        <div class="card mb-4">
          <div class="card-header d-flex justify-content-between bg-primary text-white">
            <h5 class="mb-0">Edit Employee Details</h5>
            <button type="submit" class="btn btn-primary px-5 mb-0">Update</button>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="form-group col-md-1">
                <label for="prefix_selection">Prefix</label>
                <select id="prefix_selection" class="form-control" name="prefix_selection">
                  <option value="HRPSP/" {{ str_starts_with($employee1->employee_id, 'HRPSP/') ? 'selected' : '' }}>HRPSP</option>
                  <option value="HRPSP/JZ/" {{ str_starts_with($employee1->employee_id, 'HRPSP/JZ/') ? 'selected' : '' }}>HRPSP/JZ</option>
                  <option value="JZ/" {{ str_starts_with($employee1->employee_id, 'JZ/') ? 'selected' : '' }}>JZ</option>
                  <option value="PMCL/" {{ str_starts_with($employee1->employee_id, 'PMCL/') ? 'selected' : '' }}>PMCL</option>
                  <option value="HRPSP/RD/" {{ str_starts_with($employee1->employee_id, 'HRPSP/RD/') ? 'selected' : '' }}>HRPSP/RD</option>
                </select>
                @error('prefix_selection')
                  <div class="alert alert-danger">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-2">
                <label for="employee_id">Employee ID</label>
                <input type="text" name="employee_id" id="employee_id" class="form-control" value="{{ $employee1->employee_id }}" />
                @error('employee_id')
                  <div class="alert alert-danger">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-3">
                <label for="name">Name</label>
                <input type="text" name="name" class="form-control" id="name" value="{{ $employee1->name }}" placeholder="Name" />
                @error('name')
                  <div class="alert alert-danger">{{ $message }}</div>
                @enderror
              </div>
              <div class="form-group col-md-3">
                <label for="designation_id">Designation</label>
                <select class="form-control" name="designation_id" id="designation_id">
                  @foreach($designations as $designation)
                    <option value="{{ $designation->id }}" {{ $employee1->designation_id == $designation->id ? 'selected' : '' }}>
                      {{ $designation->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-md-3">
                <label for="department_id">Department</label>
                <select class="form-control" name="department_id" id="department_id">
                  @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ $employee1->department_id == $department->id ? 'selected' : '' }}>
                      {{ $department->name }}
                    </option>
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
                <label for="grade">Grade</label>
                <select class="form-control" name="grade" id="grade">
                  <option value="" disabled>Select one</option>
                  @foreach($grades as $grade)
                    <option value="{{ $grade->name }}" {{ $employee1->grade == $grade->name ? 'selected' : '' }}>
                      {{ $grade->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-md-2">
                <label for="roles">Role</label>
                <select class="form-control" name="roles" id="roles">
                  <option value="" disabled>Select Role</option>
                  @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ $user_role == $role->name ? 'selected' : '' }}>
                      {{ $role->name }}
                    </option>
                  @endforeach
                </select>
              </div>
              <div class="form-group col-md-3">
    <label for="region_id">Region</label>
    <select id="region_id" name="region_id[]" multiple class="form-control">
        @foreach($regions as $region)
            <option value="{{ $region->id }}" 
                {{ is_array($employee1->region_ids) && in_array($region->id, $employee1->region_ids) ? 'selected' : '' }}>
                {{ $region->name }}
            </option>
        @endforeach
    </select>
    @error('region_id')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror
</div>



              <div class="form-group col-md-3">
                <label for="sub_region_id">Sub-Region</label>
                <select class="form-control" name="sub_region_id" id="sub_region_id">
                  <option value="" disabled>Select one</option>
                  @foreach($sub_regions as $sub_region)
                    <option value="{{ $sub_region->id }}" {{ $employee1->sub_region_id == $sub_region->id ? 'selected' : '' }}>
                      {{ $sub_region->name }}
                    </option>
                  @endforeach
                </select>
              </div>
           
              <div class="form-group col-md-2">
    <label for="report_to">Report To</label>
    <select name="report_to" class="form-control" id="report_to">
        <option value="" disabled>Select Employee</option>
        @foreach($employees as $employee)
            <option value="{{ $employee['employee_id'] }}" 
                {{ $employee['employee_id'] == $employee1->report_to ? 'selected' : '' }}>
                {{ $employee['name'] }} ({{ $employee['role'] }})
            </option>
        @endforeach
    </select>
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
                <input type="text" name="email" class="form-control" id="email" value="{{ $employee1->email }}" placeholder="Email Address" />
              </div>
              <div class="form-group col-md-3">
                <label for="secondary_email">Secondary Email (optional)</label>
                <input type="text" name="secondary_email" class="form-control" id="secondary_email" value="{{ $employee1->secondary_email }}" placeholder="Secondary Email Address" />
              </div>
              <div class="form-group col-md-3">
                <label for="mobile">Mobile</label>
                <input type="number" name="mobile" class="form-control" id="mobile" value="{{ $employee1->mobile }}" placeholder="Mobile Number" />
              </div>
              <div class="form-group col-md-3">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" name="image" class="form-control" id="profile_picture" />
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
    // Initialize Tom Select for Region and Sub-Region
  
    const regionTS = new TomSelect('#region_id', {
        plugins: ['remove_button'],
        placeholder: 'Pick one or more regions',
        onChange: updateSubRegions // Ensure this is called when region changes
    });

    const subTS = new TomSelect('#sub_region_id', {
        plugins: ['remove_button'],
        placeholder: 'Pick a sub-region',
        create: false
    });

    // Initialize with the selected region and sub-region on page load
    const selectedRegions = @json($employee1->region_ids); 
    console.log("ali", selectedRegions);
    regionTS.setValue(selectedRegions); // Select multiple regions

    const selectedSubRegion = @json($employee1->sub_region_id); // Get the sub_region_id from the controller
    subTS.setValue(selectedSubRegion); // Select the appropriate sub-region

    // Refresh options
    regionTS.refreshOptions();
    subTS.refreshOptions();

    subTS.disable(); // Initially disabled
    const reportings = [
      { "JCS": "BDM", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "RDM": "BDM", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "BDM": "HOD", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "HOD": "HEAD", "disabled": false, "regionCheck": false, "subRegionCheck": false, "disableRegion": false, "disableSubregion": true },
      { "RDS": "BDS", "disabled": false, "regionCheck": true, "subRegionCheck": true, "disableRegion": false, "disableSubregion": false },
      { "BDS": "HOD", "disabled": false, "regionCheck": true, "subRegionCheck": false, "disableRegion": false, "disableSubregion": false },
      { "HEAD": "__", "disabled": true, "regionCheck": false, "subRegionCheck": false, "disableRegion": true, "disableSubregion": true },
      { "HRPSP TEAM": "POC USER", "disabled": false, "regionCheck": true, "subRegionCheck": false, "disableRegion": false, "disableSubregion": false },
      { "POC USER": "PROJECT POC", "disabled": false, "regionCheck": false, "subRegionCheck": false, "disableRegion": false, "disableSubregion": true },
      { "PROJECT POC": "HRPSP DIRECTORS", "disabled": false, "regionCheck": false, "subRegionCheck": false, "disableRegion": true, "disableSubregion": true },
      { "HRPSP DIRECTORS": "__", "disabled": true, "regionCheck": false, "subRegionCheck": false, "disableRegion": true, "disableSubregion": true }
    ];

    const rolesDropdown = document.getElementById('roles');
    const reportToDropdown = document.getElementById('report_to');

    
    // Function to update sub-regions based on selected regions
    function updateSubRegions() {
      const allSubRegions = @json($sub_regions);
      const selectedRegions = regionTS.getValue().map(v => Number(v));
      if (selectedRegions.length === 0) {
        subTS.disable();
        return;
      }

      subTS.clearOptions();
      const seen = new Set();
      allSubRegions.forEach(sub => {
        if (selectedRegions.includes(sub.p_region_id) && !seen.has(sub.id)) {
          seen.add(sub.id);
          subTS.addOption({ value: sub.id, text: sub.name });
        }
      });

      subTS.enable();
      subTS.refreshOptions(false);
    }

    // Function to update Report To dropdown based on selected role, regions, and sub-regions
    function updateReportTo() {
      const selectedRole = rolesDropdown.value;
      const cfg = reportings.find(r => r[selectedRole] !== undefined) || {};

      if (cfg.disableRegion) {
        regionTS.disable();
        regionTS.clear();
      } else {
        regionTS.enable();
      }

      regionTS.refreshOptions(false);

      if (cfg.disableSubregion) {
        subTS.disable();
        subTS.clear();
      } else {
        if (subTS.options.length) subTS.enable();
      }

      subTS.refreshOptions(false);

      const selRegions = regionTS.getValue();
      const selSubRegion = subTS.getValue() || '';

      Array.from(reportToDropdown.options).forEach(opt => {
        if (!opt.value) return opt.style.display = 'block';
        const optRole = opt.dataset.role;
        const optRegIds = opt.dataset.regionId.split(',');
        const optSubRegId = opt.dataset.subRegionId;

        let show = false;

        if (optRole === cfg[selectedRole]) {
          if (cfg.regionCheck && !selRegions.some(region => optRegIds.includes(region.toString()))) {
            show = false;
          } else if (cfg.subRegionCheck && optSubRegId !== selSubRegion) {
            show = false;
          } else {
            show = true;
          }
        }

        opt.style.display = show ? 'block' : 'none';
      });
    }

    // Wire events
    regionTS.on('change', () => {
      updateSubRegions();
      updateReportTo();
    });
    subTS.on('change', updateReportTo);
    rolesDropdown.addEventListener('change', updateReportTo);

    // Initialize
    updateSubRegions();
    updateReportTo();
  });
</script>

<script type="text/javascript">
  document.addEventListener("DOMContentLoaded", function() {
    const prefixSelect = document.getElementById('prefix_selection');
    const input = document.getElementById('employee_id');

   const prefixes = ["HRPSP/JZ/","HRPSP/RD/", "HRPSP/", "JZ/","PMCL/"];

    function removeExistingPrefix(value) {
      for (let prefix of prefixes) {
        if (value.startsWith(prefix)) {
          return value.substring(prefix.length);
        }
      }
      return value;
    }

    function setPrefix(newPrefix) {
      const currentValue = input.value;
      const suffix = removeExistingPrefix(currentValue);
      input.value = newPrefix + suffix;
    }

    setPrefix(prefixSelect.value);

    prefixSelect.addEventListener('change', function() {
      setPrefix(this.value);
    });

    input.addEventListener('keydown', function(e) {
      const currentPrefix = prefixSelect.value;
      if ((e.key === 'Backspace' || e.key === 'Delete') && this.selectionStart <= currentPrefix.length) {
        e.preventDefault();
      }
    });

    input.addEventListener('input', function(e) {
      const currentPrefix = prefixSelect.value;
      const numericPart = removeExistingPrefix(this.value);
      this.value = currentPrefix + numericPart.replace(/[^0-9]/g, '');
    });

    input.addEventListener('click', function() {
      const currentPrefix = prefixSelect.value;
      if (this.selectionStart < currentPrefix.length) {
        this.setSelectionRange(currentPrefix.length, currentPrefix.length);
      }
    });

    input.addEventListener('keyup', function(e) {
      const currentPrefix = prefixSelect.value;
      if (this.selectionStart < currentPrefix.length) {
        this.setSelectionRange(currentPrefix.length, currentPrefix.length);
      }
    });
  });
</script>

@endsection
