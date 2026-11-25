@php
    $filterSize = \App\Support\ListingFilters::perPage(request());
    $filterSearch = \App\Support\ListingFilters::search(request());
@endphp
<form method="GET" action="{{ url()->current() }}" data-listing-filters class="d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3" style="border-bottom:1px solid #ddd;">
    @foreach(request()->except(['page', 'search', 'perpage']) as $key => $value)
        @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
    @endforeach
    <div class="d-flex align-items-center gap-2">
        <label for="listing-perpage" class="mb-0 text-sm text-nowrap">Per Page:</label>
        <select name="perpage" id="listing-perpage" class="form-select form-select-sm" onchange="this.form.requestSubmit()">
            @foreach([5, 10, 20, 50, 'all'] as $size)
                <option value="{{ $size }}" @selected((string)$filterSize === (string)$size)>{{ $size === 'all' ? 'All' : $size }}</option>
            @endforeach
        </select>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <label for="search-bar" class="visually-hidden">Search {{ $listingLabel }}</label>
        <input type="search" name="search" id="search-bar" value="{{ $filterSearch }}" maxlength="200" placeholder="Search {{ $listingLabel }}..." class="form-control form-control-sm" style="width:250px;max-width:100%;">
        <button type="submit" class="btn btn-sm bg-gradient-primary mb-0">Search</button>
        <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary mb-0">Reset</a>
    </div>
</form>
