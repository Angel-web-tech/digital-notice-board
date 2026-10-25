# Smart Digital Notice Board

A responsive college announcement system for students and administrators. Students can browse current notices, search and filter by category or department, view full details and revisit expired notices in the archive. Administrators can publish, schedule, edit, pin and delete notices, then review SQL-backed dashboard summaries.

## Features

- Public notice board with combined search, category, department and sort filters.
- Active, scheduled and expired states calculated on the server using the Asia/Kolkata timezone.
- One-time first-admin setup with password hashing and secure login sessions.
- CSRF-protected admin operations, PDO prepared statements and server-side validation.
- Admin CRUD, urgent and pinned notice handling, dashboard counts, department/category distribution and canvas chart.
- Responsive layouts and AJAX JSON notice loading.
- Optional PDF/JPG/PNG attachments with server MIME checks, private randomized filenames, previews and safe downloads.
- Per-session notice views, most-viewed dashboard panel, admin/faculty roles, activity audit log and login lockout.
- Draft notices hidden from student pages; admin-only CSV export with spreadsheet formula protection.
- Saved light/dark theme, live India-time clock, sticky glass navigation and responsive keyboard-accessible menus.
- Urgent ticker, expiring-soon countdowns, skeleton loading, 9-notice pagination and expandable summaries.
- Illustrated campus highlights, category-coded cards, detail timeline, copy/print actions and related notices.
- Responsive admin sidebar drawer, accessible validation counters, animated dashboard totals and monthly canvas trend chart.
- Custom 404 page, reduced-motion support, campus contact footer and back-to-top control.
- Staff sign-up with pending approval, username/email sign-in, forgot/reset password, profile editing and change-password controls.
- Admin approval/rejection/disable/enable/promote workflow with the existing staff management page.

## Technologies

HTML5, custom CSS3, JavaScript, jQuery 3.7.1, JSON, PHP 8 with PDO, and MySQL. The jQuery CDN is used for AJAX; other app functionality has no frontend framework dependency.

## Folder Structure

```text
admin/       Protected dashboard and notice management pages
api/         JSON endpoints for notices, CRUD and analytics
assets/      Student/admin styles and JavaScript
config/      Local PDO configuration (not committed)
database/    Importable schema and sample data
includes/    Shared layout, authentication and helper functions
uploads/     Reserved for optional attachments
screenshots/ Project screenshots for documentation
```

## Installation (XAMPP)

1. Copy the project into `C:\xampp\htdocs\digital-notice-board`.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin/` and import `database/digital_notice_board.sql`. It creates the `digital_notice_board` database and sample notices.
4. Copy `config/database.example.php` to `config/database.php`. Set the MySQL host, database, username and password for your machine. The standard XAMPP values are commonly `127.0.0.1`, `digital_notice_board`, `root`, and an empty password; confirm your local setup.
5. Open `http://localhost/digital-notice-board/setup-admin.php` and create the first administrator. Setup closes after the first user is created.
6. Browse the student board at `http://localhost/digital-notice-board/`. Admin sign-in is at `http://localhost/digital-notice-board/login.php`.

## Upgrade Existing Installation (MySQL Workbench)

1. Back up the `digital_notice_board` schema first. The upgrade script is additive and does not delete users or notices, but keep a backup before changing schema.
2. Open MySQL Workbench and connect to the same MySQL server used by `config/database.php`.
3. Before execution, expand `digital_notice_board` in Workbench and confirm the existing `users` and `notices` tables are present. Right-click `digital_notice_board` and choose **Set as Default Schema**. Open `database/upgrade_phase_b.sql` in Workbench and confirm the active connection is correct.
4. Execute the whole script once. It adds the notice status/views/attachment columns and creates `activity_log` and `login_attempts`.
5. Refresh the schema tree. Verify `notices.status`, `notices.views`, `notices.attachment_path`, `notices.attachment_original_name`, and the `activity_log` and `login_attempts` tables.
6. Do not execute this migration a second time: `ALTER TABLE ADD COLUMN` is intentionally one-time. If Workbench reports that a column already exists, stop and inspect the schema rather than rerunning or removing data.
7. Ensure Apache can write to the project `uploads/` directory. The included `.htaccess` disables indexes and blocks executable extensions.

To create a faculty test account after the upgrade, sign in as an admin, open **Manage users**, enter a unique username, a password of at least 8 characters containing a letter and number, confirm it, select **Faculty**, and create the user. Faculty can add/edit notices but cannot delete, export CSV, manage users or view the activity log.

The fresh-install `database/digital_notice_board.sql` already includes these Phase B structures and sample notices.

### Account-flow database upgrade

1. Back up the existing schema again before applying this separate upgrade.
2. In MySQL Workbench, set `digital_notice_board` as the default schema.
3. Open `database/upgrade_accounts.sql` and execute it once. It only adds nullable profile columns, defaults existing users to `active`, and creates `password_resets`.
4. Refresh the tree and confirm `users.full_name`, `users.email`, `users.status`, `users.must_change_password`, and `password_resets` exist. Do not rerun the `ALTER TABLE` migration.

### Staff registration and email setup

- Copy `config/app.example.php` to `config/app.php`. Set `base_url` to your site URL and optionally set `staff_registration_code`. If the code is non-empty, staff must enter it to register.
- Copy `config/mail.example.php` to `config/mail.php`. For Gmail, enable 2-Step Verification, create a Google App Password, then set SMTP host `smtp.gmail.com`, port `587`, secure mode `tls`, Gmail username/from address, and the App Password. Never use your normal Gmail password.
- Gmail setup details: in Google Account security, enable 2-Step Verification, create an App Password for “Mail”, copy the generated 16-character value into `password`, and set both `username` and `from_address` to that Gmail address. Keep `config/mail.php` untracked.
- Copy `config/app.example.php` to `config/app.php`. Keep `base_url` as `http://localhost/digital-notice-board`; optionally add a private `staff_registration_code` to require an invitation code.
- PHPMailer source is vendored in `includes/phpmailer/` without Composer. When SMTP is absent or fails, the generic response is unchanged and the reset URL is written to `logs/mail.log`; `logs/` is denied by Apache and ignored by Git. Use this fallback for a local demo and open the log on the server.
- Sign in as admin, open **Manage users**, and create a Faculty account for testing. Or use `register.php` and approve the pending account from Manage Users.

## Role Permissions

| Role | Add | Edit | Delete | CSV export | Manage users | Activity log |
|---|---|---|---|---|---|---|
| Admin | Yes | Yes | Yes | Yes | Yes | Yes |
| Faculty | Yes | Yes | No | No | No | No |

Account states: `pending` waits for admin approval, `active` may sign in, `rejected` is not approved, and `disabled` is temporarily blocked. Password reset uses a random token; only its SHA-256 hash is stored and the link expires after 30 minutes.

## Screenshots

Add screenshots to `screenshots/` and link them here, for example:

| Page | Screenshot |
|---|---|
| Student homepage (light, desktop) | `screenshots/home-light-desktop.png` |
| Student homepage (dark, phone) | `screenshots/home-dark-mobile.png` |
| Urgent ticker and expiring soon | `screenshots/home-urgent-expiring.png` |
| Filter chips and Load more | `screenshots/home-filters-pagination.png` |
| Notice details and related notices | `screenshots/notice-details.png` |
| Notice archive | `screenshots/archive.png` |
| Admin dashboard and charts | `screenshots/admin-dashboard.png` |
| Admin mobile sidebar | `screenshots/admin-mobile-menu.png` |
| Add/edit form counters and validation | `screenshots/admin-form-validation.png` |
| Custom 404 page | `screenshots/not-found.png` |
| Manage users and faculty role | `screenshots/manage-users.png` |
| Activity log and filters | `screenshots/activity-log.png` |
| Attachment preview and download | `screenshots/notice-attachment.png` |
| Draft status and student hiding | `screenshots/draft-management.png` |

## Test Cases

See [TEST_CASES.md](TEST_CASES.md) for the manual and database-backed verification checklist.

## Author and Class

Author: Student name to be added  
Class: Class/section to be added