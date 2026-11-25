<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <div class="row">
          <div class="form-group col-md-3 @error('name') is-invalid @enderror">
            <label for="name">Name</label>
            <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $category->name ?? '') }}" placeholder="Ticket Category Name" maxlength="255" required>
            @error('name')
              <div class="alert alert-danger">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-md-2 @error('code') is-invalid @enderror">
            <label for="code">Code</label>
            <input type="text" name="code" class="form-control" id="code" value="{{ old('code', $category->code ?? '') }}" placeholder="Ticket Category Code" maxlength="50" required>
            @error('code')
              <div class="alert alert-danger">{{ $message }}</div>
            @enderror
          </div>
          <div class="form-group col-md-3 @error('ticket_type_id') is-invalid @enderror">
            <label for="ticket_type_id">Ticket Type</label>
            <select name="ticket_type_id" id="ticket_type_id" class="form-control" required>
              <option value="">Select Ticket Type</option>
              @foreach ($tickettypes as $tickettype)
                <option value="{{ $tickettype->id }}" {{ old('ticket_type_id', $category->ticket_type_id ?? '') == $tickettype->id ? 'selected' : '' }}>{{ $tickettype->name }}</option>
              @endforeach
            </select>
            @error('ticket_type_id')<div class="alert alert-danger">{{ $message }}</div>@enderror
          </div>
          <div class="form-group col-md-3">
            <div class="text-center">
              <button type="submit" class="btn btn-primary w-100 mt-4 ">{{ isset($category) ? 'Update' : 'Create' }}</button>
            </div>
          </div>
        </div>
        @isset($category)
          <div class="text-sm text-muted mt-2">
            Created At: {{ $category->created_at?->format('d M Y, h:i A') ?? '-' }}<br>
            Updated At: {{ $category->updated_at?->format('d M Y, h:i A') ?? '-' }}
          </div>
        @endisset
      </div>
    </div>
  </div>
</div>
