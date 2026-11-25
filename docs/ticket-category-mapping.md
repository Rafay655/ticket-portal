# Approved category mapping

Based on the existing ticket_settings links, checked against historical tickets:

| Code | Ticket Category | Existing Ticket Type |
| --- | --- | --- |
| ITA | IT & Asset Management | HRPSP POC |
| HRE | HR & Employee Separation | HRPSP POC |
| PAY | Payroll & Compensation | HRPSP POC |
| COM | Commission & Incentives | HRPSP POC |
| STM | Sales & Target Management | Jazzcash |
| WER | Workplace & Employee Relations | Jazzcash |

IT: laptop issuance. HR: resignation acceptance, experience letter, final settlement. Payroll: salary, payslip, fuel/CMA, tax, leave/absence pay. Commission: commission calculations/disbursement and incentives. Sales: target transparency/favoritism. Workplace: facilities, work environment, toxic atmosphere, management disputes.

The 11 screenshot recommendations are applied. Existing Pay slip Not Available matches Payslip Not Available without renaming the record. Priorities absent from the screenshot are preserved.

Run after the hierarchy migrations:

    php artisan db:seed --class=TicketCategoryMappingSeeder --force

The seeder runs in one transaction, preserves all existing IDs, tickets and legacy settings, and can be rerun without duplicates. It stops and rolls back on missing records, conflicting category codes, conflicting previous Ticket Types or existing category mappings. Unknown additional Sub-Categories remain unchanged for admin review. It does not clear existing categories.

Priority changes also affect existing Open tickets because first-response escalation reads their current Sub-Category priority.
