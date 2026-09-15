# Rosemont Student Grade Viewing System

A full-stack Student Grade Viewing System built with **PostgreSQL, PHP 8+ (PDO), HTML5, CSS3, and vanilla JavaScript**. Students log in to view their grades, teachers encode and update grades for their subjects, and administrators manage students, teachers, subjects, academic terms, user accounts, and grade records.

## Tech Stack

- **Database:** PostgreSQL
- **Backend:** PHP 8+, PDO (prepared statements), session-based auth, `password_hash()` / `password_verify()`
- **Frontend:** HTML5, CSS3 (glassmorphism design), vanilla JavaScript, JSON APIs

## Project Structure

```
student-grade-system/
├── config/database.php      # Database connection (EDIT THIS)
├── includes/                # Shared PHP: session, helpers, header/footer
├── public/                  # Login, logout, entry point, CSS, JS
├── student/                 # Student dashboard, grades, profile
├── teacher/                 # Teacher dashboard, subjects, grade management
├── admin/                   # Admin dashboard + management pages
├── api/                     # JSON API endpoints used by JavaScript
└── database/schema.sql & seed.sql
```

## Setup Instructions

### 1. Create the PostgreSQL database

```bash
createdb grade_system
# or, from psql:
# CREATE DATABASE grade_system;
```

### 2. Run the schema script

```bash
psql -U postgres -d grade_system -f database/schema.sql
```

### 3. Run the seed (sample data) script

```bash
psql -U postgres -d grade_system -f database/seed.sql
```

### 4. Configure the database connection

Open `config/database.php` and update these constants to match your local PostgreSQL setup:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'grade_system');
define('DB_USER', 'postgres');
define('DB_PASSWORD', 'postgres'); // change this
```

### 5. Start the PHP application

From the **project root** (the folder containing `config/`, `public/`, etc.):

```bash
php -S localhost:8000
```

### 6. Open the application in a browser

Go to:

```
http://localhost:8000/public/login.php
```

(Visiting `http://localhost:8000/public/index.php` also works — it redirects to the login page or your dashboard automatically.)

## Test Accounts

| Role  | Login ID | Password    |
|-------|----------|-------------|
| Admin | admin    | admin123    |
| Teacher | T-1001 | teacher123  |
| Teacher | T-1002 | teacher123  |
| Student | S-2001 | student123  |
| Student | S-2002 | student123  |
| Student | S-2003 | student123  |
| Student | S-2004 (deactivated, for testing) | student123 |

## Testing Checklist

1. **Student login** — sign in as `S-2001` / `student123`; you should land on the Student Dashboard.
2. **Teacher login** — sign in as `T-1001` / `teacher123`; you should land on the Teacher Dashboard.
3. **Admin login** — sign in as `admin` / `admin123`; you should land on the Admin Dashboard.
4. **Student grade viewing** — as a student, open "My Grades", try the term filter and the search box.
5. **Teacher grade encoding** — as a teacher, open "Grade Management", pick a subject/term with a `—` grade, enter a value, and click Save. A toast notification should confirm the save and the status badge should update instantly.
6. **Teacher grade updating** — edit an already-graded record and save again; the new value should persist after a page refresh.
7. **Admin CRUD** — as admin, try adding a student (Students page), adding a teacher (Teachers page), adding a subject (Subjects page), adding/activating an academic term (Academic Terms page), resetting a password (User Accounts page), and editing a grade record (Grade Records page).
8. **Logout** — click "Log Out" in the sidebar of any role; you should be returned to the login page and the session should be destroyed (pressing Back should not show the dashboard again).
9. **Unauthorized access** — while logged in as a student, try visiting `http://localhost:8000/admin/dashboard.php` or `http://localhost:8000/teacher/dashboard.php` directly. You should be redirected back to your own dashboard, not shown the other role's page. Try the same while logged out entirely — you should be sent to the login page.
10. **Deactivated account** — try logging in as `S-2004` / `student123`; you should see an "account has been deactivated" message instead of being logged in.

## Security Notes

- Passwords are hashed with `password_hash()` (bcrypt) and verified with `password_verify()` — nothing is ever stored in plain text.
- All database queries use PDO prepared statements.
- Every dashboard and API endpoint checks the session role before running (`requireRole()` / `requireApiRole()` in `includes/session.php`).
- All user-supplied text is escaped with `htmlspecialchars()` (the `clean()` helper) before being echoed into HTML.
- Client-side validation (in the browser) is a convenience only — every API endpoint re-validates input on the server, since JavaScript can always be bypassed.
- Raw PHP/database errors are never shown to the browser; they are logged server-side and a generic message is shown instead.

## Notes on Scope

This system intentionally has **no Enrollment table and no Teaching Assignment table**. A teacher's subjects/students and a student's subjects are both derived directly from the `grades` table (which already links student + subject + teacher + term), keeping the schema to exactly six tables: `users`, `students`, `teachers`, `subjects`, `academic_terms`, `grades`.
