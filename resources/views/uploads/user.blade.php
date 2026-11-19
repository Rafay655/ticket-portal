<div class="container ">
    <h3 class="mb-4 text-start">Import Users</h3>

    <!-- Success Message -->
   
    
 

    <!-- File Upload Form -->
    <form action="{{ route('import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="import-card">
            <div class="mb-3">
                <label for="employeeFile" class="form-label"><strong>Import User File</strong></label>
                <input class="form-control" type="file" id="employeeFile" name="employeeFile" required>
                @error('employeeFile')
                    <div class="text-danger">{{ $message }}</div>
                @enderror
            </div>
            <div class="mb-3">
            <a href="{{ asset('samples/users.xlsx') }}" class="text-primary" style="font-size:12px" download>Sample Import File</a>
            </div>
        </div>
        <div class="d-block mt-4 text-start">
            <button type="submit" class="btn btn-primary">Import</button>
        </div>
    </form>
</div>