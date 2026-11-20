# API / Workflow Notes

## JSON endpoint
### `POST /ai_chat.php`
Requires an authenticated Client session and form field `csrf_token`.
Request fields: `question` (string), `csrf_token` (string).
Response JSON:
```json
{"answer":"...","escalated":false}
```
If the enabled knowledge base has no suitable match, or the question appears project-specific/pricing-related, an `ai_escalations` row is created, Managers receive an in-app notification, and `escalated` is `true`.

## Server-rendered workflows
- `POST new_inquiry.php`: creates an inquiry, stores allowed attachments, notifies Managers, and audits the action.
- `POST manage_inquiries.php`: updates inquiry status and response; notifies client.
- `POST quotations.php`: creates itemized draft quotations, sends them to clients, and converts accepted quotations into projects.
- `POST my_inquiries.php`: client accepts, declines, or requests quotation revision.
- `POST projects.php`: Manager updates status, dates, client visibility, creates tasks, and assigns personnel.
- `POST my_tasks.php`: assigned personnel update task status and notes.
- `POST inventory.php`: authorized stock movement updates inventory quantity and records a transaction; insufficient stock is rejected.
- `POST report_issue.php`: personnel submits delay, damage, shortage, or other issue alert.
- `POST manage_users.php`: Manager creates team accounts and configures per-person material-recording permissions.
- `POST ai_knowledge.php` / `POST ai_escalations.php`: maintain approved knowledge and answer escalations.

All mutating forms use CSRF tokens. Role guards are defined in `auth.php`.
