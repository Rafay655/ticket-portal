@php
    $typeField = $typeField ?? 'tickettype_id';
    $categoryField = 'ticket_category_id';
    $subCategoryField = $subCategoryField ?? 'ticketsubtype_id';
    $includeSubCategory = $includeSubCategory ?? true;
    $selectedValue = function ($field, $default = null) {
        $value = old($field, $default);
        return is_scalar($value) ? (string) $value : '';
    };
    $selectedType = $selectedValue($typeField, $selectedType ?? null);
    $selectedCategory = $selectedValue($categoryField, $selectedCategory ?? null);
    $selectedSubCategory = $selectedValue($subCategoryField, $selectedSubCategory ?? null);
@endphp
<div class="row col-12 m-0 p-0" data-ticket-hierarchy>
    <div class="form-group col-md-4">
        <label for="{{ $typeField }}" class="form-control-label">Ticket Type</label>
        <select class="form-control" name="{{ $typeField }}" id="{{ $typeField }}" data-ticket-type required>
            <option value="">Select Ticket Type</option>
            @foreach ($tickettypes as $tickettype)
                @if (empty($filterPocTypes) || $tickettype->name !== 'HRPSP POC' || auth()->user()->hasAnyRole(['JCS', 'RDM', 'RDS', 'HRPSP TEAM']))
                    <option value="{{ $tickettype->id }}" {{ $selectedType === (string) $tickettype->id ? 'selected' : '' }}>{{ $tickettype->name }}</option>
                @endif
            @endforeach
        </select>
        @error($typeField)<div class="alert alert-danger">{{ $message }}</div>@enderror
    </div>
    <div class="form-group col-md-4">
        <label for="{{ $categoryField }}" class="form-control-label">Ticket Category</label>
        <select class="form-control" name="{{ $categoryField }}" id="{{ $categoryField }}" data-ticket-category data-selected="{{ $selectedCategory }}" required>
            <option value="">Select Ticket Type first</option>
        </select>
        @error($categoryField)<div class="alert alert-danger">{{ $message }}</div>@enderror
    </div>
    @if ($includeSubCategory)
        <div class="form-group col-md-4">
            <label for="{{ $subCategoryField }}" class="form-control-label">Ticket Sub-Category</label>
            <select class="form-control" name="{{ $subCategoryField }}" id="{{ $subCategoryField }}" data-ticket-sub-category data-selected="{{ $selectedSubCategory }}" required>
                <option value="">Select Ticket Category first</option>
            </select>
            @error($subCategoryField)<div class="alert alert-danger">{{ $message }}</div>@enderror
        </div>
    @endif
    <script type="application/json" data-ticket-hierarchy-options>@json($hierarchyData)</script>
</div>
