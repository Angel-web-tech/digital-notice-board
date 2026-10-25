# Test Cases

Run these after importing `database/digital_notice_board.sql` for a fresh install, or after applying `database/upgrade_phase_b.sql` to an existing database, then sign in as an admin. Phase B DB-backed cases require the schema upgrade first. Record actual results on the local XAMPP machine.

| ID | Feature | Input | Expected result | Actual result | Status |
|---|---|---|---|---|---|
| TC-01 | Valid admin login | Existing username and correct password | Session starts and dashboard opens | Not run | Not run |
| TC-02 | Invalid admin login | Wrong password | Generic sign-in error; remain logged out | Not run | Not run |
| TC-03 | Username validation | Fewer than 3 chars, over 30 chars, or punctuation | Inline/server validation rejects username | Not run | Not run |
| TC-04 | Password validation | Password shorter than 8 chars | Inline/server validation rejects password | Not run | Not run |
| TC-05 | CSRF login protection | Missing or altered token | Request rejected | Not run | Not run |
| TC-06 | Unauthorized admin page | Open dashboard while signed out | Redirect to login | Not run | Not run |
| TC-07 | Unauthorized API | Call dashboard or save API while signed out | JSON 401 response, no HTML | Not run | Not run |
| TC-08 | First admin setup | Valid username/password before any user exists | User stored with password hash | Not run | Not run |
| TC-09 | Setup closes | Reopen setup after admin exists | Creation form unavailable | Not run | Not run |
| TC-10 | Add notice | Valid title, description, category, department, priority and dates | Notice inserted and appears on board/manage list | Not run | Not run |
| TC-11 | Title validation | Empty, 4 chars, or 151 chars | Rejected with title field error | Not run | Not run |
| TC-12 | Description validation | Empty, 9 chars, or 2001 chars | Rejected with description field error | Not run | Not run |
| TC-13 | Category validation | Alter category to unknown value in request | Rejected server-side | Not run | Not run |
| TC-14 | Department validation | Alter department to unknown value in request | Rejected server-side | Not run | Not run |
| TC-15 | Priority validation | Alter priority to unknown value in request | Rejected server-side | Not run | Not run |
| TC-16 | Invalid date input | Invalid publish or expiry date | Rejected with corresponding date error | Not run | Not run |
| TC-17 | Wrong date range | Expiry at or before publish | Rejected with expiry error | Not run | Not run |
| TC-18 | Past expiry for new notice | New notice expiry in the past | Rejected by server validation | Not run | Not run |
| TC-19 | Edit notice | Change existing title/description and save | Database row updates, new values display | Not run | Not run |
| TC-20 | Delete notice | Confirm delete in manage table | Row deleted; cancel leaves it unchanged | Not run | Not run |
| TC-21 | CSRF mutation | Missing/invalid token on save or delete | JSON error; database unchanged | Not run | Not run |
| TC-22 | Search | Search a unique word in title/description | Matching notices returned; 100-char limit | Not run | Not run |
| TC-23 | Category filter | Select Events | Only matching category returned | Not run | Not run |
| TC-24 | Department filter | Select CSE | CSE and All Departments notices returned | Not run | Not run |
| TC-25 | Combined filters | Search term + category + department | All filters apply together | Not run | Not run |
| TC-26 | Urgent display | Active urgent notice | Red urgent badge appears | Not run | Not run |
| TC-27 | Urgent status | Urgent scheduled or expired notice | Not counted as active urgent | Not run | Not run |
| TC-28 | Active visibility | Publish <= current time and expiry > current time | Appears on student board | Not run | Not run |
| TC-29 | Scheduled handling | Publish date in the future | Hidden from active board; status Scheduled in admin | Not run | Not run |
| TC-30 | Expired hidden | Expiry <= current time | Hidden from active board | Not run | Not run |
| TC-31 | Expired archive | Expiry <= current time | Appears in archive and remains in MySQL | Not run | Not run |
| TC-32 | All Departments display | Filter to one department | All Departments notices also match | Not run | Not run |
| TC-33 | Dashboard counts | Compare cards with SQL status predicates | Total/Active/Urgent/Scheduled/Expired match SQL | Not run | Not run |
| TC-34 | Dashboard breakdown | Compare categories/departments to grouped SQL | Chart/list counts match database rows | Not run | Not run |
| TC-35 | Database failure | Stop MySQL or use invalid local credentials | Safe message; details only in PHP error log | Not run | Not run |
| TC-36 | Failed AJAX | Block API request or stop Apache | Friendly error state/toast; no broken page | Not run | Not run |
| TC-37 | JSON response | Request each API endpoint | JSON content type and consistent response shape | Not run | Not run |
| TC-38 | Responsive layout | View board/admin at phone width | No overlapping controls; cards and tables usable | Not run | Not run |
| TC-39 | Logout | Click Log out then open admin route | Session/cookie cleared; admin route redirects | Not run | Not run |
| TC-40 | Dark mode | Toggle theme, reload page | Theme changes and saved preference is restored | Not run | Not run |
| TC-41 | Urgent ticker | Active urgent notices exist | Homepage ticker links to urgent notice details | Not run | Not run |
| TC-42 | Ticker hover | Hover pointer over scrolling ticker | Ticker animation pauses and resumes on pointer leave | Not run | Not run |
| TC-43 | Expiring soon | Active notices end within 3 days | Homepage section lists them with an “Ends in X days” label | Not run | Not run |
| TC-44 | Expiring soon exclusions | Scheduled, expired, and active notices ending after 3 days | None appear in expiring section | Not run | Not run |
| TC-45 | Skeleton loading | Throttle API response | Nine skeleton cards appear while the first page loads | Not run | Not run |
| TC-46 | Server pagination | More than 9 matching notices | API returns 9 per page and correct `has_more` metadata | Not run | Not run |
| TC-47 | Load more | Click Load more with matching results available | Next page appends without removing current cards | Not run | Not run |
| TC-48 | Pagination filters | Change search/category/department after loading another page | Results reset to page 1 and use the new combined filters | Not run | Not run |
| TC-49 | Copy detail link | Click Copy link on a notice | Current URL is copied and confirmation appears | Not run | Not run |
| TC-50 | Print details | Print a notice detail page | Navigation/actions are hidden and notice content prints cleanly | Not run | Not run |
| TC-51 | Related notices | Open a notice whose category has other active notices | Up to 3 same-category active notices appear | Not run | Not run |
| TC-52 | Dashboard count-up | Load dashboard | SQL counts animate from zero to final values | Not run | Not run |
| TC-53 | Monthly trend chart | Dashboard contains notices published across months | 12-month canvas line chart matches grouped SQL data | Not run | Not run |
| TC-54 | Reduced motion | Enable OS reduced-motion preference | Ticker/skeleton animation is suppressed | Not run | Not run |
| TC-55 | Responsive student design | Open homepage at 360px, 768px and 1280px | No horizontal page overflow; cards and filters adapt | Not run | Not run |
| TC-56 | Responsive admin design | Open dashboard/manage/forms at 360px, 768px and 1280px | Sidebar, cards, charts and tables fit or scroll within their container | Not run | Not run |
| TC-57 | Dark mode contrast | Toggle dark mode on homepage, archive, details and admin pages | Text and controls remain legible; palette stays consistent | Not run | Not run |
| TC-58 | Student hamburger | At mobile width, open/close menu by button, Escape and link | Menu is keyboard operable and aria-expanded stays accurate | Not run | Not run |
| TC-59 | Admin sidebar drawer | At mobile width, open drawer and select overlay/Escape | Drawer closes and overlay does not block page afterward | Not run | Not run |
| TC-60 | Custom 404 | Request an unknown URL under project | Styled 404 appears with link back to homepage | Not run | Not run |
| TC-61 | Empty states | Apply filters with no matching notices | Illustrated friendly empty state appears without stale cards | Not run | Not run |
| TC-62 | Error state | Simulate API failure | Clear error message appears without HTML/data leakage | Not run | Not run |
| TC-63 | Toast semantics | Save/delete success or failure | Toast slides in, has role=status and auto-dismisses | Not run | Not run |
| TC-64 | Form counters | Type title and description to their limits | Live character counts update and indicate limit state | Not run | Not run |
| TC-65 | Field validity styling | Enter invalid then valid field data | Error text and invalid state clear as field becomes valid | Not run | Not run |
| TC-66 | Reduced motion | Enable prefers-reduced-motion | Mesh, card, ticker, skeleton and transition motion is minimized | Not run | Not run |
| TC-67 | Campus clock | Compare navbar clock with Asia/Kolkata time | Hours/minutes show in IST and update each minute | Not run | Not run |
| TC-68 | Filter chips | Select each category and clear filters | Chip pressed state and AJAX results match selection | Not run | Not run |
| TC-69 | Attachment valid PDF | Upload genuine PDF <=2 MB on add/edit | Random stored filename; original name shown and downloadable | Not run | Not run |
| TC-70 | Attachment valid image | Upload real JPG or PNG <=2 MB | Image preview appears on detail page | Not run | Not run |
| TC-71 | Attachment invalid MIME | Rename executable/text content to .jpg or .pdf | Server rejects because detected MIME does not match | Not run | Not run |
| TC-72 | Attachment invalid extension | Upload unsupported extension | Client and server reject the file | Not run | Not run |
| TC-73 | Attachment size limit | Upload a 3 MB file | Client and server reject it | Not run | Not run |
| TC-74 | Attachment remove/replace | Edit attached notice and remove or upload replacement | Old file removed after successful database update; new file works | Not run | Not run |
| TC-75 | Upload execution protection | Request script-like path in uploads | Server denies execution and directory listing | Not run | Not run |
| TC-76 | Session view counter | Open same published notice twice in one session | Views increase once for that session | Not run | Not run |
| TC-77 | Most viewed panel | Change published views values | Dashboard top five match database order | Not run | Not run |
| TC-78 | Create faculty user | Valid unique username, strong password, matching confirmation | Faculty user created with password hash | Not run | Not run |
| TC-79 | User validation | Invalid/duplicate username, weak password, mismatch or bad role | Inline/server errors reject request | Not run | Not run |
| TC-80 | Self-delete prevention | Admin deletes own user row | Server rejects request | Not run | Not run |
| TC-81 | Last admin protection | Delete final admin account | Server rejects request | Not run | Not run |
| TC-82 | Faculty delete restriction | Faculty submits delete API request with valid CSRF | JSON 403; notice remains unchanged | Not run | Not run |
| TC-83 | Faculty restricted pages | Faculty opens Manage Users or Activity Log | Friendly 403 Access denied page | Not run | Not run |
| TC-84 | Faculty CSV restriction | Faculty directly requests export endpoint | JSON 403; no CSV response | Not run | Not run |
| TC-85 | Faculty notice privileges | Faculty adds and edits a notice | Allowed with CSRF and full validation | Not run | Not run |
| TC-86 | Activity log creation | Add/edit/delete/login/fail login/user create/delete | Correct action logged; no password stored | Not run | Not run |
| TC-87 | Activity filters | Filter by action and user, paginate | Matching rows only; page count and Load more correct | Not run | Not run |
| TC-88 | Login lockout | Submit 5 wrong passwords for one username | Fifth attempt starts a 5-minute lock and clear wait message | Not run | Not run |
| TC-89 | Login lockout reset | Successful login after lock expires | Failure counter clears | Not run | Not run |
| TC-90 | Username privacy | Fail login for existing and unknown usernames | Same generic invalid-credential response | Not run | Not run |
| TC-91 | Password tools | Toggle visibility; test weak/medium/strong values | Password visibility and meter update without changing submitted value | Not run | Not run |
| TC-92 | CSV filtered export | Apply search/category/department/status then export | CSV includes matching rows only, UTF-8 BOM, formula cells apostrophe-prefixed | Not run | Not run |
| TC-93 | Draft hidden from students | Save draft, query search/ticker/archive and open direct URL | Draft absent from student responses; direct URL gives not found | Not run | Not run |
| TC-94 | Draft preview by staff | Admin/faculty opens draft detail URL | Draft preview allowed and visibly marked Draft | Not run | Not run |
| TC-95 | Draft management | Filter Manage Notices by Draft | Draft appears with Draft pill and can be edited | Not run | Not run |
| TC-96 | Registration name | Name shorter/longer than 3-60 chars or contains digits | Client and server reject name | Not run | Not run |
| TC-97 | Registration email | Invalid or duplicate email | Rejected with safe field error | Not run | Not run |
| TC-98 | Registration username | Invalid or duplicate username | Rejected with safe field error | Not run | Not run |
| TC-99 | Registration password | Missing letter/number or shorter than 8 | Rejected; strength meter updates | Not run | Not run |
| TC-100 | Registration confirmation | Passwords differ | Rejected | Not run | Not run |
| TC-101 | Registration code | Code configured but absent/wrong | Registration rejected | Not run | Not run |
| TC-102 | Registration rate limit | Submit 6 registrations from one IP within an hour | Sixth is rejected; no account created | Not run | Not run |
| TC-103 | Registration honeypot | Submit a value in hidden website field | Request rejected without creating account | Not run | Not run |
| TC-104 | Pending login | Correct password for pending account | Approval-waiting message; no session | Not run | Not run |
| TC-105 | Rejected/disabled login | Correct password for rejected or disabled account | Safe unavailable-account message; no session | Not run | Not run |
| TC-106 | Approve staff | Admin approves pending faculty | Status becomes active and login succeeds | Not run | Not run |
| TC-107 | Reject staff | Admin rejects pending faculty | Status becomes rejected and login is blocked | Not run | Not run |
| TC-108 | Faculty manage users | Faculty visits page and calls users API | Page returns 403; API JSON 403 | Not run | Not run |
| TC-109 | Faculty activity log | Faculty visits page and calls activity API | Page returns 403; API JSON 403 | Not run | Not run |
| TC-110 | Faculty CSV | Faculty directly calls export API | JSON 403; no CSV data | Not run | Not run |
| TC-111 | Admin promote | Admin promotes faculty to admin | Role changes and action is logged | Not run | Not run |
| TC-112 | Admin self-protection | Admin rejects/disables/demotes self | Server rejects action | Not run | Not run |
| TC-113 | Final-admin protection | Attempt to reject/disable/demote the last admin | Server rejects action | Not run | Not run |
| TC-114 | Forgot password registered email | Request reset for active account email | Same generic confirmation; reset link emailed or logged | Not run | Not run |
| TC-115 | Forgot password unknown email | Request reset for unregistered address | Exact same generic confirmation; no identifying leak | Not run | Not run |
| TC-116 | Reset rate limit | More than 3 requests per email/IP per hour | Same generic message; no additional email/token | Not run | Not run |
| TC-117 | Valid reset token | Use unexpired unused link and valid new password | Password changes, token consumed, old tokens removed | Not run | Not run |
| TC-118 | Expired/reused/tampered reset token | Submit invalid token variants | Friendly unavailable link page; no token is echoed | Not run | Not run |
| TC-119 | Mail fallback | Remove mail config or simulate SMTP failure | Generic message remains; reset URL appears only in denied log | Not run | Not run |
| TC-120 | Profile update | Update name/email with duplicate email | Valid update saves; duplicate rejected | Not run | Not run |
| TC-121 | Change password | Wrong current, same new password, weak password | Server rejects each; valid change regenerates session | Not run | Not run |
| TC-122 | Force password change | Set `must_change_password=1` | Other guarded routes redirect to change form | Not run | Not run |