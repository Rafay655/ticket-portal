# Flexi Desk — Mobile Developer Handoff

Updated: 25 November 2026

## Scope and deployment

The Laravel backend has been updated locally. Deploy the backend changes and migrations to the VPS before expecting the live mobile app to use these APIs. Native mobile UI changes have not been implemented in this repository.

Production base URL: https://ticket.myflexihr.com
Local Herd base URL: https://ticket-portal.test (only reachable from a device/emulator with appropriate network/DNS setup and a trusted certificate).

All paths below include /api. Protected requests require Authorization: Bearer <token> and Accept: application/json. Use your existing login flow. No credentials are included in this handoff.

## 1. Three-level ticket selection

Display these dependent dropdowns in this order:

**Ticket Type → Ticket Category → Ticket Sub-Category**

| Step | Request | Response |
| --- | --- | --- |
| Load Types | GET /api/ticket-types | JSON array: id, name |
| Select Type | GET /api/ticket-types/{typeId}/categories | JSON array: id, name, code, ticket_type_id |
| Select Category | GET /api/ticket-categories/{categoryId}/sub-categories | JSON array: id, name, priority, ticket_category_id |

Lookup responses are top-level arrays, not wrapped in data. Use returned IDs; do not hardcode IDs from examples or the local database.

Required app behavior:

- Changing Type clears both Category and Sub-Category.
- Changing Category clears Sub-Category.
- Disable child dropdowns until their parent is selected; show loading and retry states.
- Ignore stale lookup responses if the user has changed the parent while a request is in flight.
- Show a clear empty-state message if a parent has no mapped children.
- Refresh cached lookup data after this release. Preserve existing Sub-Category IDs in stored records.
- Priority comes from the selected Sub-Category. Do not calculate it from Category.

Create with POST /api/tickets using multipart/form-data:

~~~text
tickettype_id       = <selected Type ID>
ticket_category_id  = <selected Category ID>
ticketsubtype_id    = <selected Sub-Category ID>
description        = <ticket details>
ticket_attachment  = <optional file>
~~~

Let the HTTP client generate the multipart Content-Type boundary. Attachment limit: 10 MB; jpg, jpeg, png, pdf, doc, docx.

The backend accepts optional subject, but it is currently not persisted by the Ticket model. Do not rely on subject being returned or saved.

On success: HTTP 201; created ticket is in data. Existing ID keys tickettype_id and ticketsubtype_id have not been renamed. Category is ticket_category_id; ticket_category_name is available on serialized tickets. Historical records may have a null stored category ID, so display missing category values safely.

For older app versions only: omitting ticket_category_id is supported; the server infers it from the mapped Sub-Category. New app versions should send all three selections. Unmapped or mismatched combinations return HTTP 422 with errors keyed by the affected field. Category mismatches use ticket_category_id; Type/leaf mismatches can use ticketsubtype_id.

Edit endpoint: POST /api/tickets/{ticketId}/update. Apply the same dropdown reset and hierarchy validation rules. If the leaf is omitted, the existing leaf is used; a different Type must still match that leaf or the request will fail.

The legacy GET /api/ticket-types/{typeId}/sub-types remains supported, but new UI should use the Category-based endpoint above.

## 2. Prevent duplicate active tickets

Rule: the same requester cannot create another ticket for the same Sub-Category while an existing ticket is Open or in_progress. Closed and Feedback tickets do not block creation. The rule is enforced on the backend, including concurrent creation attempts; app checks alone are insufficient.

Example conflict response (dummy ID):

~~~json
{
  "status": false,
  "code": "duplicate_active_ticket",
  "message": "You already have an open or in-progress ticket for this Ticket Sub-Category (ticket #101). Please continue on the existing ticket instead of creating a new one.",
  "existing_ticket_id": 101,
  "existing_ticket_status": "Open",
  "existing_ticket_url": "https://ticket.myflexihr.com/api/tickets/ticket/101",
  "redirect_url": "https://ticket.myflexihr.com/tickets_view/101?return_to=..."
}
~~~

App handling:

1. Read JSON bodies for non-2xx responses; do not replace HTTP 409 with a generic invalid-response exception.
2. For 409 plus code=duplicate_active_ticket, show message in a modal.
3. After confirmation, navigate to the existing native ticket detail screen using existing_ticket_id.
4. Fetch detail with GET /api/tickets/ticket/{existing_ticket_id}.
5. Do not automatically retry ticket creation. redirect_url is a web URL, not the preferred native navigation target.
6. Disable Create while submitting to avoid repeated taps.

## 3. Comments and voice notes

POST /api/tickets/{ticketId}/reply (multipart/form-data).

| Field | Rule |
| --- | --- |
| description | Optional text, max 10,000 characters |
| comment_attachment | Optional file, max 5 MB; jpg/jpeg/png/pdf/doc/docx/xls/xlsx/txt/mp3/wav |
| voice_note | Optional audio data URI or Base64; decoded audio max 10 MB |
| is_recording | Optional boolean; 1 is rejected |

At least one non-whitespace text comment, valid file, or valid voice note is required. Empty and whitespace-only replies return HTTP 422. Voice-only and attachment-only comments are allowed. Display validation errors from errors; API error flags differ between endpoints (ticket creation uses status, reply validation uses success).

Recommended audio transport: data:audio/webm;codecs=opus;base64,<base64>. Supported data-URI audio subtypes: webm, ogg, mp4, mpeg, mp3, wav, x-wav. Send the actual recorder format; do not label WebM bytes as MP3 or WAV. Bare Base64 is supported for legacy clients and stored with a WAV extension, so prefer a correct data URI in new code.

Recorder UI must use explicit states:

~~~text
Idle → Requesting permission → Recording → Processing/encoding → Ready to send
~~~

- Disable Send during permission requests, recording, and processing/encoding.
- Register stop/data handlers before starting/stopping the recorder.
- Enable Send only after the final audio data has been received and encoded, or valid text/file content exists while the recorder is idle.
- An empty composer must keep Send disabled; also guard submission itself.
- When a recording is removed, clear its encoded value and reevaluate Send.
- On recorder failure, reset controls and release the microphone. Show an actionable permission/device error in a modal.
- Native apps must request OS microphone permission. The web portal requires HTTPS (or localhost); OS permission still applies in the native app.

The backend now saves audio as a file and includes only a reference/has_voice_note metadata in notifications. Do not expect or require Base64 audio in notification payloads. To play a recording, load ticket comments using GET /api/tickets/{ticketId}/comments and use voice_note_url. Comment notifications can contain a shortened text preview; fetch the actual comment for full content.

## 4. Status, priority and user feedback

Normalize status values for display: Open, in_progress → In Progress, Closed, Feedback. Be tolerant of case differences in legacy records. Use server priority values: Urgent, High, Medium, Low.

Show success, error and validation feedback in confirmation modals rather than transient toasts. Handle HTTP 401 by returning to authentication, HTTP 403 as unavailable access, HTTP 422 as field/content validation, and duplicate HTTP 409 as described above. If an error body is not JSON, show a fallback error without crashing.

For close actions use POST /api/tickets/{ticketId}/close. Do not call the web-only /ticket/close/{id} route from the mobile app. Preserve the originating list when returning after ticket actions, and refresh its data after successful mutations.

## 5. Current category organization

| Ticket Type | Code | Ticket Category |
| --- | --- | --- |
| HRPSP POC | ITA | IT & Asset Management |
| HRPSP POC | HRE | HR & Employee Separation |
| HRPSP POC | PAY | Payroll & Compensation |
| HRPSP POC | COM | Commission & Incentives |
| Jazzcash | STM | Sales & Target Management |
| Jazzcash | WER | Workplace & Employee Relations |

These are current local mappings based on previous Ticket Settings and historical ticket types. Fetch mappings from the API after deployment. Do not hardcode this table as authorization or lookup data. Existing ticket visibility/privacy rules still apply; Category selection must not widen access.

Updated priorities:

| Sub-Category | Priority |
| --- | --- |
| Salary Not Disbursed | Urgent |
| Laptop Issuance Status Required | Medium |
| Resignation Acceptance Letter Required | Medium |
| Experience Letter Required | Medium |
| Pay slip Not Available | Medium |
| Salary Discrepancy | High |
| Final Settlement Calculation Issue | High |
| Favoritism in Target Setting | High |
| Lack of Transparency in Target Allocation | Medium |
| Toxic Workplace Atmosphere | High |
| Dispute with Management | High |

Other priorities were preserved. First-response escalation reads the Sub-Category priority; no app-side escalation timer is required.

## 6. Acceptance checklist

- [ ] Login and load Types, Categories and Sub-Categories using real returned IDs.
- [ ] Change parent selections quickly; confirm stale children never remain selectable.
- [ ] Create a valid ticket and open its returned ID.
- [ ] Submit a mismatched hierarchy and display JSON 422 field errors.
- [ ] Create again for the same user/Sub-Category with an Open ticket; show duplicate modal and open existing ticket.
- [ ] Repeat the duplicate check with an in_progress ticket.
- [ ] Confirm a Closed/Feedback ticket permits a new ticket of the same Sub-Category.
- [ ] Verify another user's ticket does not incorrectly trigger the requester-specific duplicate rule.
- [ ] Reject empty and whitespace-only replies; allow text-only, attachment-only and voice-only replies.
- [ ] Confirm Send stays disabled throughout recording and final encoding, including stop/start and failure cases.
- [ ] Remove a voice note from an otherwise empty composer; Send becomes disabled.
- [ ] Submit a realistic audio recording and confirm notification delivery does not fail with a database size error.
- [ ] Load comments and play audio using voice_note_url.
- [ ] Handle denied microphone permission and unavailable devices gracefully.
- [ ] Verify private-ticket access remains restricted and action completion returns to the originating list.

## 7. Postman files supplied

- Flexi-Desk-Mobile-API.postman_collection.json — 112 requests, hierarchy lookups, duplicate and validation response examples.
- Flexi-Desk-Local-Herd.postman_environment.json — local HTTPS environment.
- Flexi-Desk-VPS.postman_environment.json — server environment.
- README.md — collection usage and known backend limitations.

All four files are alongside this handoff in docs/postman. Saved responses contain dummy IDs. Enter test credentials yourself. Requests can create tickets and trigger notifications; run on test data individually.
