# Flexi Desk mobile API collection

Includes 111 route declarations and 112 method/path combinations from routes/api.php. PATCH and POST /api/me/profile are separate requests. Implicit HEAD handling on GET routes is not duplicated.

## Import and use

1. Import Flexi-Desk-Mobile-API.postman_collection.json and Flexi-Desk-VPS.postman_environment.json into Postman.
2. Select the Flexi Desk VPS environment. Keep base_url as https://ticket.myflexihr.com, without /api or a trailing slash.
3. Set email and password to a dedicated test account. The included email is a dummy; passwords and tokens are blank.
4. Send Authentication > POST /api/auth/login. Its response script stores token and user_id automatically in the selected environment.
5. Fetch GET /api/ticket-types, then GET /api/ticket-types/{{ticket_type_id}}/categories, then GET /api/ticket-categories/{{ticket_category_id}}/sub-categories. Set ticket_type_id, ticket_category_id, and ticket_subtype_id from those results.
6. Create a ticket using Tickets > POST /api/tickets/. The multipart body supports an optional attachment; select a local file to enable it. A successful 201 response stores ticket_id.
7. Use ticket_id for detail, comments, reply, close, feedback and reassignment. Set assign_to from reassign/candidates. Notification IDs are UUIDs returned by GET /api/notifications.
8. Send requests individually on test data. Mutating requests are real operations, not simulations.

## Authentication and request details

- Protected requests inherit Bearer {{token}}; public requests explicitly use no authentication.
- OTP flow: send-otp, verify-otp, reset-password. Enter otp manually; verify-otp stores reset_token separately. Set new_password; reset-password requires new_password_confirmation, whereas change-password uses confirm_password.
- QR login uses qr_token, distinct from the normal bearer token. FCM registration uses fcm_token.
- JSON and multipart bodies follow validators found in the handlers. Field rules are in each request description. Optional query parameters and file fields can be enabled in Postman. File paths are not bundled.
- Profile update supports PATCH and POST; use POST for multipart uploads. The profile/team routes are throttled to 10 requests/minute; the global API limiter is 60/minute.
- Some authentication handlers report application failure with HTTP 200; inspect success/status/errors, not just the HTTP status.
- No response samples were fabricated. No live API request, login, email, or notification was sent while building this collection.

## Source limitations

Routes exist even when their delegated implementation is absent. Their empty request bodies are placeholders, not usable schemas. This inventory does not repair or certify those endpoints.

| Method | Route | Notes |
| --- | --- | --- |
| POST | /api/register | Legacy wrapper passes a generic Request into a web-controller method typed as StoreUserRequest; may fail with TypeError before validation. |
| GET | /api/test-fcm | Diagnostic route has no authentication, uses a hard-coded server-side device token, and calls sendToToken with three scalar arguments although FcmClient expects (string token, array payload). Expected to fail as written. Not executed. |
| POST | /api/tickets/ | subject is accepted by the validator but is not in Ticket::$fillable, so the current model does not persist it. |
| GET | /api/tickets/{ticket}/comments | per_page is read but the implementation uses get(), so this endpoint currently returns all comments without pagination. |
| PATCH | /api/me/profile | Both PATCH and POST are registered. For file uploads, use the POST variant to ensure PHP receives multipart files. |
| POST | /api/me/profile | Both PATCH and POST are registered. For file uploads, use the POST variant to ensure PHP receives multipart files. |
| POST | /api/v1/user/{user_id}/update | Delegates to UserController::update, which returns a web redirect rather than JSON. No API-specific validation is defined in that method. |
| GET | /api/v1/users | Delegates to UserController::index, which returns a view; legacy wrapper then treats it as an Eloquent query. |
| GET | /api/v1/hazards | Unavailable handler/dependency: App\Http\Controllers\HazardController |
| POST | /api/v1/hazards/create | Unavailable handler/dependency: App\Http\Controllers\HazardController |
| GET | /api/v1/hazards/{hazard_id}/show | Unavailable handler/dependency: App\Http\Controllers\HazardController |
| POST | /api/v1/hazards/{hazard_id}/update | Unavailable handler/dependency: App\Http\Controllers\HazardController |
| POST | /api/v1/hazards/{hazard_id}/assign | Unavailable handler/dependency: App\Http\Controllers\HazardController |
| DELETE | /api/v1/hazards/{hazard_id}/delete | Unavailable handler/dependency: App\Http\Controllers\HazardController |
| GET | /api/v1/unsafe_behaviors | Unavailable handler/dependency: App\Http\Controllers\UnsafeBehaviorController |
| POST | /api/v1/unsafe_behaviors/create | Unavailable handler/dependency: App\Http\Controllers\UnsafeBehaviorController |
| GET | /api/v1/unsafe_behaviors/{unsafe_behavior_id}/show | Unavailable handler/dependency: App\Http\Controllers\UnsafeBehaviorController |
| POST | /api/v1/unsafe_behaviors/{unsafe_behavior_id}/update | Unavailable handler/dependency: App\Http\Controllers\UnsafeBehaviorController |
| POST | /api/v1/unsafe_behaviors/{unsafe_behavior_id}/assign | Unavailable handler/dependency: App\Http\Controllers\UnsafeBehaviorController |
| DELETE | /api/v1/unsafe_behaviors/{unsafe_behavior_id}/delete | Unavailable handler/dependency: App\Http\Controllers\UnsafeBehaviorController |
| GET | /api/v1/near_misses | Unavailable handler/dependency: App\Http\Controllers\NearMissController |
| POST | /api/v1/near_misses/create | Unavailable handler/dependency: App\Http\Controllers\NearMissController |
| GET | /api/v1/near_misses/{near_miss_id}/show | Unavailable handler/dependency: App\Http\Controllers\NearMissController |
| POST | /api/v1/near_misses/{near_miss_id}/update | Unavailable handler/dependency: App\Http\Controllers\NearMissController |
| POST | /api/v1/near_misses/{near_miss_id}/assign | Unavailable handler/dependency: App\Http\Controllers\NearMissController |
| DELETE | /api/v1/near_misses/{near_miss_id}/delete | Unavailable handler/dependency: App\Http\Controllers\NearMissController |
| GET | /api/v1/fpdamages | Unavailable handler/dependency: App\Http\Controllers\FirePropertyDamageController |
| POST | /api/v1/fpdamages/create | Unavailable handler/dependency: App\Http\Controllers\FirePropertyDamageController |
| GET | /api/v1/fpdamages/{fpdamage_id}/show | Unavailable handler/dependency: App\Http\Controllers\FirePropertyDamageController |
| POST | /api/v1/fpdamages/{fpdamage_id}/update | Unavailable handler/dependency: App\Http\Controllers\FirePropertyDamageController |
| POST | /api/v1/fpdamages/{fpdamage_id}/assign | Unavailable handler/dependency: App\Http\Controllers\FirePropertyDamageController |
| DELETE | /api/v1/fpdamages/{fpdamage_id}/delete | Unavailable handler/dependency: App\Http\Controllers\FirePropertyDamageController |
| GET | /api/v1/injuries | Unavailable handler/dependency: App\Http\Controllers\InjuryController |
| POST | /api/v1/injuries/create | Unavailable handler/dependency: App\Http\Controllers\InjuryController |
| GET | /api/v1/injuries/{injury_id}/show | Unavailable handler/dependency: App\Http\Controllers\InjuryController |
| POST | /api/v1/injuries/{injury_id}/update | Unavailable handler/dependency: App\Http\Controllers\InjuryController |
| POST | /api/v1/injuries/{injury_id}/assign | Unavailable handler/dependency: App\Http\Controllers\InjuryController |
| DELETE | /api/v1/injuries/{injury_id}/delete | Unavailable handler/dependency: App\Http\Controllers\InjuryController |
| GET | /api/v1/ptws | Unavailable handler/dependency: App\Http\Controllers\PermitToWorkController |
| POST | /api/v1/ptws/create | Unavailable handler/dependency: App\Http\Controllers\PermitToWorkController |
| GET | /api/v1/ptws/{ptw_id}/show | Unavailable handler/dependency: App\Http\Controllers\PermitToWorkController |
| POST | /api/v1/ptws/{ptw_id}/update | Unavailable handler/dependency: App\Http\Controllers\PermitToWorkController |
| DELETE | /api/v1/ptws/{ptw_id}/delete | Unavailable handler/dependency: App\Http\Controllers\PermitToWorkController |
| GET | /api/v1/ie_audits | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditClauseController |
| POST | /api/v1/ie_audits/create | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditClauseController |
| GET | /api/v1/ie_audits/{ie_audit_id}/show | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditClauseController |
| POST | /api/v1/ie_audits/{ie_audit_id}/update | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditClauseController |
| DELETE | /api/v1/ie_audits/{ie_audit_id}/delete | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditClauseController |
| GET | /api/v1/ie_audits/answers | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditAnswerController |
| GET | /api/v1/ie_audits/questions | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditQuestionController |
| GET | /api/v1/line_chart | Unavailable handler/dependency: App\Http\Controllers\LineChartController::index |
| GET | /api/v1/line_chart/all_timelines | Unavailable handler/dependency: App\Http\Controllers\LineChartController::prepareForLineChart |
| GET | /api/v1/card_chart | Unavailable handler/dependency: App\Http\Controllers\CardChartController::index |
| GET | /api/v1/card_chart/all_timelines | Unavailable handler/dependency: App\Http\Controllers\CardChartController::prepareForPiChart |
| GET | /api/v1/bar_charts | Unavailable handler/dependency: App\Http\Controllers\BarChartController::prepareForBarChart |
| POST | /api/v1/ie_audits/answers/submit | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditAnswerController |
| POST | /api/v1/ie_audits/answers/{answer_id}/update | Unavailable handler/dependency: App\Http\Controllers\InternalExternalAuditAnswerController |
| GET | /api/v1/incidents | Unavailable handler/dependency: App\Http\Controllers\IncidentController |
| POST | /api/v1/incident_assign | Unavailable handler/dependency: App\Http\Controllers\IncidentAssignController |
| POST | /api/v1/incident_assign/reject | Unavailable handler/dependency: App\Http\Controllers\IncidentAssignController |
| POST | /api/v1/incident_assign/{incident_assign_id}/update | Unavailable handler/dependency: App\Http\Controllers\IncidentAssignController |
| GET | /api/v1/notifications | Unavailable handler/dependency: App\Http\Controllers\NotificationController::index |
| POST | /api/v1/notifications | Unavailable handler/dependency: App\Http\Controllers\NotificationController::activitySeen |
| POST | /api/v1/reports/generate | Unavailable handler/dependency: App\Http\Controllers\ReportController::createReport |
| DELETE | /api/v1/reports/{report_id}/delete | Unavailable handler/dependency: App\Http\Controllers\ReportController::destroy |

api-coverage.json maps every request to its source and known limitations.

## Duplicate ticket creation

POST /api/tickets returns HTTP 409 with code duplicate_active_ticket if the same user already has an Open or in_progress ticket for the same subtype. Read message and navigate the mobile app to existing_ticket_id. existing_ticket_url fetches the API detail; redirect_url opens the website. The collection saves that existing ID into ticket_id. Resolved tickets do not block a new request.


## Three-level ticket hierarchy

Use GET /api/ticket-types, then GET /api/ticket-types/{ticketType}/categories, then GET /api/ticket-categories/{ticketCategory}/sub-categories. Submit tickettype_id, ticket_category_id, and ticketsubtype_id. Existing mobile clients can keep sending the original type/subtype fields; category is inferred from a mapped sub-category. Invalid/unmapped combinations return 422. Existing subtype IDs are preserved. Administrators must map pre-existing categories and subtypes before using them for new tickets.


## Latest updates — 25 November 2026

For Herd import Flexi-Desk-Local-Herd.postman_environment.json (https://ticket-portal.test). For the server import Flexi-Desk-VPS.postman_environment.json. Backend changes must be deployed before testing the VPS. Select one environment and enter your test-account credentials.

Saved response examples use dummy IDs. Creation returns 409 for the same requester/Sub-Category with an Open or In Progress ticket; use existing_ticket_id to open it. The response script saves that ID for subsequent detail requests. Closed/Feedback tickets allow a new ticket. Invalid hierarchy returns 422.

Reply accepts text, a file, or valid audio; empty replies return 422. Voice notes are stored separately from notification data. The voice_note field is disabled until a recording is provided. Do not submit while the mobile recorder is active or processing.
