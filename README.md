StudyPlan - Student Study Organizer (PHP + MySQL)
================================================

Contents:
- htdocs/ (place in XAMPP's htdocs or into a virtual host folder)
  - index.html        (frontend)
  - css/style.css
  - js/app.js
  - api/               (backend PHP APIs)
    - db.php
    - api.php
  - assets/           (icons placeholder)
- sql/
  - studyplan_schema.sql  (database schema + sample data)

Setup (XAMPP on Windows):
1. Start XAMPP, enable Apache and MySQL.
2. Copy the `htdocs` folder to your XAMPP installation folder, typically `C:\xampp\htdocs\studyplan`.
   Alternatively, copy entire project and create an Apache alias/virtual host pointing to `.../studyplan_app/htdocs`.
3. Create MySQL database and user:
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database `studyplan_db` (or any name you prefer)
   - Import `sql/studyplan_schema.sql` via phpMyAdmin -> Import
   - The SQL file also inserts sample user, subjects and tasks.
4. Edit `api/db.php` and adjust DB credentials if necessary (default: root, no password)
5. Access frontend at: http://localhost/studyplan/index.html (or folder name you used)

Security note:
- This is a demo starter app. For a production app, add authentication, CSRF protection, input validation and harden DB credentials.
- Passwords are stored as hashed values in SQL (password_hash in PHP recommended for registration).

Files included:
- Frontend: simple, responsive layout matching the provided screenshot layout.
- Backend: single API endpoint `api/api.php` that accepts POST `action` and returns JSON.
  Actions: get_dashboard, get_subjects, add_subject, update_subject, delete_subject, get_tasks, add_task, update_task, delete_task
