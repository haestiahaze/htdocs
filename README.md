# ForgeFlow — Project & Inventory Management System

A PHP 8 + MySQL starter application for a glass/aluminum fabrication and installation business. It includes separate Client, Personnel, and Manager portals, secure password hashing, role guards, CSRF checks, uploads, stock movements, audit logs, notifications, quotation workflow, project/task assignment, client visibility controls, and a manager-maintained knowledge base for the AI-style assistant.

## Requirements
- XAMPP (Apache + PHP 8+ + MySQL/MariaDB)
- PHP extensions: `pdo_mysql`, `fileinfo`, `mbstring`
- Modern browser

## Install
1. Copy the `fabrication_project_inventory` folder into `C:\\xampp\\htdocs\\`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin and import `database.sql`. It creates `fabrication_management`.
4. If your MySQL user/password differ from XAMPP defaults, edit `database.php`.
5. Visit `http://localhost/fabrication_project_inventory/`.
6. Ensure the `uploads` directory can be written by PHP. Uploaded files are limited to PDF/JPG/PNG/WebP and 8 MB each.

## Demo logins
The seed uses `manager@example.com`, `personnel@example.com`, and `client@example.com`, password `Demo123!`.

**Security:** For production, create fresh accounts and remove or disable demo accounts immediately. Never use demo credentials on a public deployment.

## Main files
- `database.sql` — relational schema and seed data
- `database.php` — PDO database connection
- `auth.php` — login session, role guards, CSRF and audit helpers
- `login.php`, `logout.php`, `register.php` — authentication
- `client_dashboard.php`, `personnel_dashboard.php`, `manager_dashboard.php` — role-specific dashboards
- `new_inquiry.php`, `my_inquiries.php`, `manage_inquiries.php` — inquiry workflow
- `quotations.php` — quotation line items, send, accept/revise/decline, conversion
- `projects.php`, `my_projects.php`, `my_tasks.php` — projects, task assignment, progress and visibility
- `inventory.php`, `report_issue.php` — stock movement and personnel issue reports
- `ai_assistant.php`, `ai_chat.php`, `ai_knowledge.php`, `ai_escalations.php` — constrained knowledge lookup and manager escalation
- `manage_users.php` — create team accounts and set personnel material permissions
- `reports.php`, `reports_export.php`, `audit_logs.php`, `notifications.php`
- `style.css`, `app.js`, `partials.php`

## REST/API note
`ai_chat.php` is a JSON POST endpoint. It requires a signed-in Client session and a valid CSRF token. The rest of this starter app uses secure server-rendered PHP POST forms. The database schema is designed to extend into `/api/v1/...` endpoints if a separate REST client is needed.

## Important scope notes
This is a working starter/MVP, not a production-audited commercial deployment. The assistant is a keyword-based retrieval tool, not a hosted LLM; it only searches enabled Manager-maintained knowledge entries and escalates uncertain or pricing/project-specific questions. Email delivery, PDF exports, calendar UI, richer image progress feeds, pagination/search across every table, purchase order receiving screens, and a full JSON REST API are planned extensions. CSV report export is included and can be opened in Excel. Before public deployment, add rate limiting, password reset/email verification, upload malware scanning, stronger server-side validation, HTTPS, backups, and security review.
