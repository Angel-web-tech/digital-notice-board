# Five-Minute Demo Script

## 0:00-0:30: Introduce the project

Open the student home page. Explain that students can browse announcements while administrators manage them through a protected area.

Screenshot: `screenshots/student-board.png` showing the first viewport and notice cards.

## 0:30-1:20: Student board filters

Search for a notice, then combine a category and department filter. Point out the urgent badge, pinned notice and expiry information. Open a card to show the complete detail page.

Screenshot: `screenshots/combined-filters.png`.

## 1:20-1:45: Archive and scheduled notices

Open the archive and explain that expired rows stay in the database. Briefly show an upcoming notice in admin as Scheduled and explain it is not shown on the active board.

Screenshot: `screenshots/archive.png`.

## 1:45-2:15: Authentication

Sign out and try opening the dashboard directly to demonstrate access control. Sign back in with the admin created during setup.

Screenshot: `screenshots/admin-login.png`.

## 2:15-3:10: Dashboard analytics

Show total, active, urgent, scheduled and expired counts. Explain that they are returned by the dashboard JSON endpoint and computed by SQL. Point out category chart, department totals, recent notices and urgent list.

Screenshot: `screenshots/admin-dashboard.png`.

## 3:10-4:20: Create and manage a notice

Create a notice with a valid date range. Demonstrate an inline validation error, correct it, publish the notice, then edit or delete it from the management table.

Screenshot: `screenshots/notice-form.png` and `screenshots/manage-notices.png`.

## 4:20-5:00: Security and wrap-up

Explain server-side validation, prepared statements, password hashing, CSRF tokens and the single source of status rules. Close with the SQL schema and test-case checklist.