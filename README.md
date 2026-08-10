# Faye WorkNest

A personal productivity dashboard built in PHP + MySQL for managing School Works, Research Works, Personal Projects, and Learning — all in one place.

## Requirements
- PHP 7.4+ (uses PDO MySQL)
- MySQL / MariaDB
- A local server stack: XAMPP, WAMP, Laragon, or MAMP (recommended for beginners)

## Setup Instructions

1. **Copy the project folder** into your server's web root:
   - XAMPP: `C:/xampp/htdocs/faye-worknest`
   - Laragon: `C:/laragon/www/faye-worknest`
   - MAMP: `/Applications/MAMP/htdocs/faye-worknest`

2. **Create the database.**
   - Open phpMyAdmin (or the MySQL CLI).
   - Import `schema.sql` — this creates the `faye_worknest` database and all tables automatically.
   - Example CLI: `mysql -u root -p < schema.sql`

3. **Configure the database connection.**
   - Open `config/db.php`.
   - Update `DB_HOST`, `DB_USER`, and `DB_PASS` to match your MySQL setup (defaults are `localhost` / `root` / empty password, which works out of the box with XAMPP/Laragon).

4. **Start your server** (Apache + MySQL) via your control panel (e.g. XAMPP Control Panel).

5. **Open the app** in your browser:
   ```
   http://localhost/faye-worknest/
   ```
   There's no login screen — it opens straight to the Dashboard. The app auto-creates a single default account on first visit, so everything just works out of the box.

6. **Uploads folder permissions.**
   The `uploads/` folder (used by Research Works → Documents) must be writable by the web server. On Linux/Mac: `chmod 755 uploads`.

## Features Included

- 🚫 **No login required** — opens straight to the Dashboard; a single account is created automatically on first run
- 📊 **Dashboard** — stat cards (Tasks Today, Upcoming Deadlines, Active Projects, Skills Learning), Today's Tasks, Progress Overview, Upcoming Deadlines, Current Projects, Learning Progress, Upcoming Events, mini Calendar, and a daily quote
- 🎓 **School Works** — Activities (table view), Assignments/Projects, Events — each with priority, status, and full CRUD
- 🔬 **Research Works** — Activities (table view), Deadlines of Forms, Research Progress (per-stage bars), Documents (file upload)
- 💼 **Projects** — project cards with progress rings, a workspace per project (to-do checklist), a Project Goals tab (categorized: Business / Development / Learning / Other) and Milestones
- 📖 **Learning Studies** — Skills shown as circular progress rings with personal notes, week-by-week Learning Plan, Resources to Learn library
- 📅 **Calendar** — month view aggregating deadlines/events/activities from every module with a color-coded legend, plus custom calendar items
- 🌿 **Habits to Change** — new habit tracker: add habits, check off "Today", see weekly Mon–Sun progress dots and running streaks, plus overview stats (active habits, best streak, average consistency)
- 🔔 **Notifications** — auto-generated reminders for assignments due in 2 days, mark as read
- 👤 **Profile** — edit name/email, quick stats
- 🔍 Global search bar across all modules
- 🌙 Light/dark theme toggle (saved in the browser)

## Design

Redesigned to match the "Faye WorkNest" mockups: a bird-logo sidebar with tagline, purple/lavender theme with colored stat cards, circular progress rings, table-style activity lists, and a habit-tracking module.

## Notes / Assumptions

- The app is single-user by design — there's no login screen, and all data belongs to the one auto-created account.
- The Calendar aggregates real dates from other modules automatically; you can also add one-off custom entries.
- This is a functional foundation — feel free to ask for additional polish (e.g. drag-and-drop, charts, email reminders, dark mode) and it can be extended further.

## File Structure

```
faye-worknest/
├── config/db.php              # Database connection
├── schema.sql                 # Full database schema
├── includes/                  # header, sidebar, footer, auth check
├── actions/                   # Form-handling / CRUD logic per module
├── css/style.css              # All styling
├── js/script.js                # Modal + form helper scripts
├── uploads/                    # Uploaded research documents
├── index.php                                                       (redirects straight to dashboard)
├── dashboard.php
├── school_works.php
├── research_works.php
├── personal_projects.php / project_view.php / project_goals.php   (nav label: "Projects")
├── learning.php                                                    (nav label: "Learning Studies")
├── calendar.php
├── habits.php                                                      (nav label: "Habits to Change")
├── notifications.php
├── profile.php
└── search.php
```
