# Ticket Type → Ticket Category → Ticket Sub-Category

## Admin setup

1. Open Admin Panel → Tickets → Ticket Categories. Edit each existing category and select its Ticket Type.
2. Open Ticket Sub-Categories. Edit each existing subtype and select its Ticket Type and Ticket Category. Keep or update its existing priority.
3. Create tickets using Type, Category, then Sub-Category. Each dropdown is filtered by its parent.

Existing categories and subtypes are deliberately left unassigned by the migration. Unassigned records remain visible in the admin lists and cannot be used for new ticket creation until mapped. No existing IDs or ticket records are replaced. Existing tickets keep their original references; their category is displayed from a matching mapping when available, otherwise "Not assigned". New tickets store the selected category ID.

## Existing behavior

- The existing ticket_sub_types table and ticketsubtype_id request field are retained; only the displayed name changes to Ticket Sub-Category.
- Priority and first-response SLA still come from the final sub-category. Duplicate checks still use requester + final sub-category ID for Open/In Progress tickets.
- Assignment and HRPSP POC privacy rules remain based on Ticket Type.
- A category with child sub-categories/tickets cannot be deleted or moved to another type. A sub-category used by tickets cannot be moved to another category or deleted. Unmapped legacy records can be assigned without changing their IDs.
- Legacy Ticket Settings records are retained. New/edited settings must match the hierarchy.

## Mobile APIs

- GET /api/ticket-types
- GET /api/ticket-types/{ticketType}/categories
- GET /api/ticket-categories/{ticketCategory}/sub-categories
- Legacy GET /api/ticket-types/{ticketType}/sub-types remains and returns mapped sub-categories with their original id/name fields.

POST /api/tickets accepts tickettype_id, ticket_category_id, and ticketsubtype_id. Old mobile clients may omit ticket_category_id; it is inferred from the selected, mapped sub-category. Invalid/unmapped parent combinations return JSON 422. Duplicate active tickets return 409 with existing-ticket navigation details. Mobile UI code is not part of this repository; displaying three dropdowns in the native app requires an app update.

## Imports and reports

Imports accept the old ticket_sub_type column or the new ticket_sub_category name. Optional ticket_category can contain the category name or code; include it when a sub-category name is ambiguous. New imported rows must resolve to one mapped Type/Category/Sub-Category combination. Reports, Excel exports and ticket notification emails display all three levels.

## Deployment

The new hierarchy migration was applied only to the local localhost/hrpsp_tickets database during this task. Existing counts and IDs were checked before/after and were unchanged. No production/VPS migration was run.

After deploying the code, apply the additive migrations (Laravel skips those already recorded):

    php artisan migrate --path=database/migrations/2026_11_25_000001_create_ticket_categories_table.php --force
    php artisan migrate --path=database/migrations/2026_11_25_000002_add_ticket_hierarchy_relationships.php --force
    php artisan db:seed --class=TicketCategoryPermissionSeeder --force
    php artisan optimize:clear
    php artisan queue:restart

Complete the admin mapping steps before using old records in new tickets. Category permission setup is additive and does not create sample categories or reclassify existing data.
