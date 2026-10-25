# Presentation Outline

## Slide 1: Smart Digital Notice Board
- College announcement system
- Student and administrator views
- Student name, class and institution

## Slide 2: Problem
- Paper notices can be missed
- Updates are spread across channels
- Old announcements are hard to find

## Slide 3: Objectives
- Centralize campus announcements
- Make notices searchable and filterable
- Provide secure administration and archiving

## Slide 4: Technologies
- HTML5 and custom CSS3
- JavaScript and jQuery AJAX
- PHP 8, PDO and JSON
- MySQL database

## Slide 5: Architecture
- Browser renders pages and controls
- jQuery calls PHP JSON endpoints
- PHP validates and queries using PDO
- MySQL stores notices and accounts

## Slide 6: Database Design
- Users table for hashed admin credentials
- Notices table with dates and priority
- Foreign key, indexes and date-order check
- Sample data across categories and departments

## Slide 7: Key Features
- Search and combined category/department filters
- Active, scheduled and expired notice handling
- Urgent and pinned notices
- Admin CRUD and analytics dashboard

## Slide 8: Validation and Security
- Server-side field validation
- CSRF tokens on writes
- Password hashing and protected sessions
- PDO prepared statements and output escaping

## Slide 9: Testing
- Authentication and access control
- CRUD and filter cases
- Date/status and archive cases
- Dashboard SQL count comparison

## Slide 10: Conclusion and Future Work
- Easier discovery of official announcements
- Reusable status logic and responsive pages
- Optional PDF/image attachments
- Email notifications and role expansion