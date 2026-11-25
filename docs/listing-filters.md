# Listing filters

The 16 paginated web listings use server-side search with GET query parameters.

- Search and page size are carried in the URL and retained in pagination links and browser reloads.
- Submitting a new search or page size returns to page one. Reset clears the filters.
- Page sizes are 5, 10, 20, 50, and All. Invalid sizes fall back to 10.
- Admin forms share resources/views/listings/filters.blade.php. Tickets and employees retain their existing controls and counts.
- App\Support\ListingFilters groups text and related-model searches inside the original access scopes.
- DataTables search is disabled on paginated listings to avoid filtering only the current page.
- Ticket search supports ID, description, status, date, hierarchy names, category code, priority, requester, and assignee.
- No database migration is required. Existing role and ticket visibility rules still apply.
