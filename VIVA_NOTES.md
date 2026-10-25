# Viva Notes

## Where Each Technology Is Used

- HTML5: semantic page structure in `index.php`, `notice-details.php`, `admin/` pages and shared layout.
- CSS3: responsive student design in `assets/css/style.css` and admin layout in `assets/css/admin.css`.
- JavaScript: card rendering and admin form/date/chart behavior in `assets/js/`.
- jQuery: AJAX requests, event handling and rendering in both JavaScript files.
- JSON: API responses from `api/notices.php`, `api/save-notice.php`, `api/delete-notice.php` and `api/dashboard-stats.php`.
- PHP: routing, session authentication, validation, reusable helpers and page generation.
- MySQL: stores users/notices; PDO prepared statements handle queries and transactions.

## File Responsibilities

- `includes/helpers.php`: timezone, constants, escaping, CSRF, JSON, validation and shared status rules.
- `includes/auth.php`: secure session and admin-only access checks.
- `config/database.php`: reusable PDO connection and MySQL timezone.
- `database/digital_notice_board.sql`: database schema, indexes, foreign key and sample notices.
- `api/`: JSON endpoints for the browser and dashboard.
- `admin/`: protected dashboard, add/edit form and notice management.
- `assets/`: shared CSS and browser JavaScript.
- `assets/js/theme.js`: dark mode toggle and saved browser preference.
- `assets/js/date-format.js`: shared client-side display date formatter.
- `assets/css/enhancements.css`: additive student theme, ticker, skeleton, print and related-notice styles.
- `assets/css/admin-enhancements.css`: admin theme and monthly chart styles.

## Request Flow

Browser page -> jQuery AJAX request -> PHP API endpoint -> PDO prepared SQL -> MySQL -> JSON response -> JavaScript updates the page.

The notice API returns 9 notices per page with pagination metadata. The homepage can request subsequent pages with the Load more button; the first page also includes active urgent ticker entries and active notices expiring within three days.

The homepage also displays an active urgent ticker that pauses on hover, skeleton cards while the first AJAX request is pending, and a saved dark mode preference. Notice details offer a copyable URL, print-specific styling and up to three active related notices from the same category.

Admin changes additionally include a session check and CSRF token. PHP validates every submitted field before a database write. The dashboard calculates status counts directly in SQL.

## Validation Rules

- Username: 3-30 letters, numbers or underscores. Password: at least 8 characters.
- Title: required, 5-150 characters. Description: required, 10-2000 characters.
- Category, department and priority must match allowed server-side lists.
- Dates must parse strictly; expiry must be after publish. New notices cannot expire in the past.
- Search is trimmed and limited to 100 characters.
- Client validation improves feedback; PHP validation is authoritative.
- Dark mode is a CSS-variable theme selected in the navbar and saved in `localStorage`.
- The dashboard count-up and monthly line chart use plain JavaScript and canvas; monthly totals are grouped by SQL.

## Likely Viva Questions

1. **Why use PDO prepared statements?** They separate SQL from user input and help prevent SQL injection.
2. **How are passwords stored?** `password_hash()` stores a one-way hash; `password_verify()` checks a login.
3. **What is CSRF?** A forged cross-site request; a random session token is checked for state-changing forms.
4. **How does AJAX help?** The page can fetch and render notices without a full reload.
5. **Why return JSON?** It gives JavaScript a predictable data format separate from page markup.
6. **How are notice states defined?** Active, scheduled and expired predicates are centralized in `includes/helpers.php` and matched in SQL.
7. **Why keep expired notices?** They remain useful as an archive and are not automatically deleted.
8. **What is a database index?** A structure that speeds lookup/filtering on fields such as category and dates.
9. **Why use a transaction when saving?** It keeps a database write atomic and permits rollback on failure.
10. **How does the admin dashboard chart work offline?** It draws SQL-returned counts on an HTML canvas using plain JavaScript.

## Phase B Features and Database Changes

For an existing installation, run `database/upgrade_phase_b.sql` once from MySQL Workbench after backing up the schema. It only adds columns and creates tables; it does not delete accounts or notice rows.

- Attachments: `admin/notice-form.php`, `api/save-notice.php`, `api/delete-notice.php`, `api/download-attachment.php`, `notice-details.php`, and `uploads/.htaccess`. PHP `finfo` verifies MIME as well as extension; files are limited to 2 MB and saved under random names. The database stores the random path and original display name.
- Views: `notice-details.php` stores viewed notice IDs in the session and increments the `views` column once per session. The admin dashboard displays the five most-viewed published notices.
- Users and roles: `admin/manage-users.php` and `api/users.php` create hashed admin/faculty users and prevent self-delete or removing the final admin. Admin can use all tools. Faculty can add/edit notices but cannot delete, export, manage users or inspect logs.
- Activity log: `activity_log` stores action, username, optional user/notice IDs, title and time. Password values are never included. `admin/activity-log.php` and `api/activity-log.php` provide filtered, paginated admin access.
- Login protection: `login_attempts` stores username, IP, timestamp and success flag. Five recent failures lock the username for five minutes; a successful login clears that user's failure history.
- CSV export: `api/export-notices.php` reuses the management filters, sends UTF-8 BOM, and prefixes formula-leading cells with an apostrophe.
- Draft status: the `notices.status` column distinguishes Draft from Published. Shared predicates in `includes/helpers.php` require Published for active/scheduled/expired/urgent rules; student direct URLs and all public collections hide drafts.

## Role Permissions

| Role | Add/edit | Delete | CSV | Manage users | Activity log |
|---|---|---|---|---|---|
| Admin | Yes | Yes | Yes | Yes | Yes |
| Faculty | Yes | No | No | No | No |

## Staff Account Flow

1. A staff member submits name, email, username and password on `register.php`. PHP validates the fields, honeypot and IP limit, hashes the password and creates a `faculty` user with `pending` status.
2. An admin reviews pending rows in `admin/manage-users.php`. `api/users.php` approves, rejects, disables, enables, promotes or deletes accounts, and also prevents deleting the signed-in admin or the final admin.
3. `login.php` accepts username or email. It checks the existing failed-attempt lockout, verifies the password and then requires `status='active'`. Wrong credentials remain generic; correct credentials on a pending account get the approval message.
4. Forgot-password requests use `forgot-password.php`. A random 32-byte token is sent by email or written to the private demo mail log; only SHA-256(token) is stored in `password_resets`. `reset-password.php` checks it with a constant-time hash comparison, expires it after 30 minutes and marks it used.
5. Signed-in users can edit their profile in `admin/profile.php` and change their password in `admin/change-password.php`.

The account migration adds `full_name`, unique nullable `email`, `status`, and `must_change_password` to existing users, plus `password_resets`. Existing users receive active status by the column default. It does not remove user or notice records.

| Role | Notice add/edit | Notice delete | CSV | Manage users | Activity log |
|---|---|---|---|---|---|
| Admin | Allowed | Allowed | Allowed | Allowed | Allowed |
| Faculty | Allowed | Forbidden | Forbidden | Forbidden | Forbidden |

## Design Techniques Used

- **Box model:** `assets/css/style.css` and `assets/css/admin.css` use global `box-sizing: border-box`, spacing tokens and stable grid tracks so components do not unexpectedly grow.
- **Positioning:** `assets/css/style.css` uses sticky `.site-header`, relative card/hero layers and fixed `.back-to-top`; `assets/css/admin.css` uses sticky desktop `.admin-sidebar` and fixed mobile drawer controls.
- **Z-index:** `--z-sticky`, `--z-dropdown`, `--z-modal` and `--z-toast` are defined in `assets/css/style.css`; admin overlay and toast layers reuse them in `assets/css/admin.css`.
- **Hover/focus:** student card lifts, category chips, links and action buttons use transitions in `assets/css/style.css`; visible `:focus-visible` rings are shared across inputs and controls.
- **Text styling:** Georgia display headings and Arial body/control text establish hierarchy in `assets/css/style.css`; `assets/css/admin.css` keeps dense admin forms/tables readable.
- **Image styling:** campus scenes are inline SVGs in `index.php` and `404.php`; `.highlight-art` and `.hero-art` styling is in `assets/css/style.css`.
- **Variables:** palette, 8px spacing, radii, shadows, speed and z-index tokens are declared once in the `:root` block in `assets/css/style.css`; dark mode overrides tokens using `:root[data-theme="dark"]`.
- **Media queries:** 1000px, 760px, 480px and 360px layout changes are in `assets/css/style.css`; 1050px, 760px and 460px admin changes are in `assets/css/admin.css`.
- **Animations/transitions:** mesh, hero headline, card entrance, ticker, skeleton and reduced-motion behavior live in `assets/css/style.css`; drawer, toast, count-up and chart drawing are controlled by `assets/js/site.js` and `assets/js/admin.js`.
- **Timers:** `assets/js/site.js` calls `setInterval(updateClock, 60000)` for the IST clock; `assets/js/admin.js` uses `requestAnimationFrame` for count-up numbers and a timeout for toast dismissal.
- **DOM and events:** `assets/js/script.js` builds card elements with jQuery `.text()` and binds category, filter, expand and Load more events; `assets/js/site.js` manages navigation, scrolling and keyboard Escape.
- **AJAX and JSON:** `assets/js/script.js` reads `api/notices.php` responses for filters/pagination/ticker/expiring cards; `assets/js/admin.js` reads dashboard stats and submits CRUD actions.
- **jQuery effects:** `.slideToggle()` handles the mobile filter panel and card summary; `.fadeIn()` staggers incoming notices; toast dismissal uses `.fadeOut()`.