<div class="">
    <div class="col-md-4">
        <div class="form-group">
            <label for="customSelect" style="text-align:start;display:block;font-size:14px">Assigned to</label>
            <select class="form-control custom-select" style="font-size:12px" id="customSelect4">
                <option selected disabled value="">Select one...</option>

                <!-- Loop through the assignedUsers to populate the dropdown -->
                @foreach($assignedUsers as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

    </div>
    <div class="form-group col-md-4">
        <div class="form-group">
            <label for="startDate" style="text-align:start;display:block;font-size:14px">From</label>
            <input type="date" class="form-control custom-select" style="font-size:12px" id="startDate4"
                placeholder="Select start date">
        </div>
    </div>
    <div class="form-group col-md-4">
        <div class="form-group">
            <label for="endDate" style="text-align:start;display:block;font-size:14px">To</label>
            <input type="date" class="form-control custom-select" style="font-size:12px" id="endDate4"
                placeholder="Select end date">
        </div>
    </div>
</div>