# TTU Clinic Management System — Project Documentation

*Compiled from direct inspection of the codebase (commit `7f64b3c`, branch `master`). Every claim below was verified against the actual migrations, models, controllers, routes, middleware, views, configuration, and the passing test suite — nothing here is reconstructed from memory or design intent.*

---

## 1. Project Overview

The TTU Clinic Management System is a role-based web application for operating a university health clinic. It lets students and staff book short appointment slots at the clinic, lets doctors manage those appointments and record visit outcomes, and gives administrators oversight of users, medication stock, doctor staffing, and system activity. The application is bilingual (Arabic/English, with full RTL support) and supports both light and dark themes.

The system is built around four roles — **student**, **staff**, **doctor**, and **admin** — each with a distinct dashboard and permission set enforced server-side.

---

## 2. Technology Stack

Verified directly from `composer.json`, `composer.lock`, `package.json`, and the local environment.

**Backend**
- PHP 8.3+ (local environment runs PHP 8.5.7)
- Laravel Framework v13.21.1
- Laravel Breeze v2.4 (server-rendered authentication scaffolding)
- Laravel Tinker, Pail, Pint, Pao (dev tooling)

**Frontend**
- Blade templates (server-rendered, no SPA framework)
- Tailwind CSS v4.3+ (with `@tailwindcss/forms`)
- Alpine.js v3.4 (used for interactive UI, e.g. the doctor's visit-report modal)
- Chart.js v4.5 (admin dashboard charts)
- Vite v8 + `laravel-vite-plugin` for asset bundling
- Hand-built neumorphic (soft-UI) design system in `resources/css/app.css` — not a third-party library

**Database**
- MySQL (`ttu_clinic`) in the development environment (`DB_CONNECTION=mysql` in `.env`)
- SQLite used automatically for the test suite

**Testing**
- PHPUnit 12.5 via Laravel's test runner
- 178 tests / 545 assertions, all passing (verified by running `php artisan test`)

**Other infrastructure**
- Laravel's database-backed queue and scheduler (no Redis/external queue in use)
- `MAIL_MAILER=log` and `BROADCAST_CONNECTION=log` — see §7 for what this means in practice

---

## 3. Feature List by Role

### All authenticated users
- Email/identifier + password login, registration, password reset (Laravel Breeze)
- Role-aware dashboard redirect (`/dashboard` routes to the correct role dashboard)
- In-app notification bell with unread count, mark-one-read and mark-all-read
- Language switching between Arabic and English, persisted per session
- Dark/light theme toggle, persisted per browser (`localStorage`)
- Logout

### Student & Staff
- **Booking system**: view a 3-day rolling window (today + 2 days) of appointment slots and book a confirmed slot directly (no approval step)
- **One active booking at a time**: a user cannot hold two confirmed, not-yet-passed bookings simultaneously; the UI shows their current active booking with a cancel option instead of the slot grid
- **My Medications**: read-only history of past visit reports and prescribed medications tied to the user's own bookings
- **Contact a doctor**: send a message to any doctor in the system; see the doctor's reply in the notification panel

### Doctor
- Dashboard showing the same 3-day window of all confirmed clinic bookings (not filtered to a specific doctor — see §7), with patient name, time, and any existing visit report
- Cancel a patient's booking (notifies the patient)
- Create or edit a visit report for a booking: condition, examination, diagnosis, treatment plan, notes, and a list of prescribed medications with quantities
- **Automatic attendance check-in on login** — no manual "clock in" button; logging in is treated as arriving at the clinic
- Manual check-out button (attendance is also auto-closed by a scheduled job at 16:00 if the doctor forgets)
- Reply to patient contact messages from the notification panel

### Admin
- Dashboard with stat tiles and charts (weekly booking volume, role breakdown, hourly occupancy) built with Chart.js
- User management: paginated, searchable, role-filterable list of students/staff/doctors; activate/deactivate accounts (deactivating an admin account is blocked)
- Per-user activity history view
- Doctor management: create doctor accounts and set their weekly working-day schedule; edit doctor details/schedule later
- Identifier whitelist management ("university records"): add or revoke the student/staff IDs allowed to self-register (see §7 — this is an explicitly simulated identity check, not a real university system integration)
- Medication catalogue: add medications, edit name/unit/low-stock threshold, restock quantity, activate/deactivate a medication
- Daily attendance roster: which doctors are scheduled today, who has actually checked in, and who is scheduled tomorrow
- System-wide activity log, filtered to admin-performed actions

### Cross-cutting features
- **Booking concurrency safeguards** — see §6
- **Activity logging** — most state-changing actions (booking created/cancelled, visit report saved, doctor check-in/out, admin user/medication/record changes) are recorded to an audit trail, stored as a translation key + parameters so a log entry renders correctly in whichever language the viewer currently has active, regardless of the language active when the event happened
- **Scheduled automation** (Laravel scheduler, registered in `bootstrap/app.php`):
  - `attendance:auto-checkout` — closes out any doctor still checked in at 16:00
  - `notifications:send-reminders` — runs every 5 minutes, notifies patients of bookings starting within roughly the next hour, and marks a `reminded_at` timestamp so the same booking is never reminded twice

---

## 4. Database Schema

11 application (domain) tables, plus Laravel's standard framework tables (`sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`). No soft deletes are used anywhere; deletions are hard and cascade via foreign keys.

### `users`
| Column | Type | Notes |
|---|---|---|
| `role` | enum | `student`, `staff`, `doctor`, `admin` (widened from 3 to 4 values in a later migration) |
| `identifier` | string, unique | student/staff ID number used for login/registration |
| `is_active` | boolean, default `true` | deactivated accounts are blocked at login |
| `name`, `email` (unique), `password`, `email_verified_at`, `remember_token`, timestamps | | standard fields |

### `university_records`
| Column | Type | Notes |
|---|---|---|
| `identifier` | string, unique | not a foreign key — matched by value at registration, then discarded |
| `type` | enum | `student`, `staff` |
| `is_valid` | boolean, default `true` | |

A local, admin-managed whitelist. The registration controller's own source comment (in Arabic) explicitly calls this "the fake university database" — see §7.

### `bookings`
| Column | Type | Notes |
|---|---|---|
| `user_id` | FK → users, cascade | |
| `booking_date` | date | |
| `booking_hour` | tinyint unsigned | clinic hours 8–15 (8:00 AM–4:00 PM) |
| `booking_minute` | tinyint unsigned, default `0` | 5-minute granularity within the hour |
| `price` | decimal(5,2), default `0.25` | display/record value only — see §7 |
| `status` | enum, default `confirmed` | `confirmed`, `cancelled` |
| `active_slot_key` | string, nullable, **unique** | derived automatically; `null` unless `status = confirmed` (see §6) |
| `reminded_at` | timestamp, nullable | set once the 5-minute reminder job has notified this booking |

Note: there is **no `doctor_id` column**. Bookings are not routed to a specific doctor — any doctor sees and can act on every confirmed booking (see §7).

### `visit_reports`
| Column | Type | Notes |
|---|---|---|
| `booking_id` | FK → bookings, **unique**, cascade | enforces a true 1:1 with a booking |
| `doctor_id` | FK → users | |
| `condition`, `examination` | text | |
| `diagnosis`, `treatment_plan`, `notes` | text, nullable | |

### `visit_report_medications` (pivot)
| Column | Type | Notes |
|---|---|---|
| `visit_report_id` | FK → visit_reports, cascade | |
| `medication_id` | FK → medications, cascade | |
| `quantity` | unsigned int, default `1` | |

Unique on `(visit_report_id, medication_id)`. Backed by a custom pivot model (`VisitReportMedication`) with its own auto-incrementing `id`.

### `medications`
| Column | Type | Notes |
|---|---|---|
| `name`, `unit` (nullable) | string | |
| `stock_quantity` | unsigned int, default `0` | decremented when a visit report prescribes it |
| `low_stock_threshold` | unsigned int, default `10` | drives `isLowStock()` — a passive UI indicator, not an alert (see §7) |
| `is_active` | boolean, default `true` | soft-disable, hides it from new prescriptions without deleting history |

### `doctor_schedules`
| Column | Type | Notes |
|---|---|---|
| `doctor_id` | FK → users, **unique** | one schedule row per doctor (1:1) |
| `working_days` | json | array of Carbon `dayOfWeek` integers, 0 (Sunday) – 6 (Saturday) |

### `doctor_attendance`
| Column | Type | Notes |
|---|---|---|
| `doctor_id`, `date` | FK / date | unique pair — one row per doctor per day |
| `check_in_at` | datetime | set automatically on login |
| `check_out_at` | datetime, nullable | set manually or by the 16:00 auto-checkout job |
| `is_auto_checkout` | boolean, default `false` | true if the scheduled job closed the day, not the doctor |

### `activity_logs`
| Column | Type | Notes |
|---|---|---|
| `user_id` | FK → users, cascade | the acting user |
| `action` | string | machine key, e.g. `booking_cancelled`, `doctor_check_in` |
| `description` | text, nullable | JSON-encoded `{key, params}` — a translation key and its parameters, rendered live in the viewer's current locale |

### `messages`
| Column | Type | Notes |
|---|---|---|
| `sender_id`, `recipient_id` | FK → users, cascade | |
| `body` | text | |
| `parent_message_id` | FK → messages, nullable, `nullOnDelete` | self-referential — null on the original message, set on a reply |

No `read_at`/`is_read` column — there is no read/unread tracking for messages at the database level.

### `notifications`
Standard Laravel polymorphic notifications table (`id` UUID, `type`, `notifiable_type`/`notifiable_id`, `data`, `read_at`, timestamps). In practice always attached to a `User`.

### Relationships summary
- A `User` (student/staff) **has many** `Booking`s; a `Booking` **belongs to** one `User`.
- A `Booking` **has one** `VisitReport`; a `VisitReport` **belongs to** a `Booking` and a doctor `User`.
- A `VisitReport` **belongs to many** `Medication`s through `visit_report_medications` (carrying a `quantity`); a `Medication` belongs to many `VisitReport`s.
- A `User` (doctor) **has one** `DoctorSchedule` and **has many** `DoctorAttendance` records.
- A `User` **has many** `ActivityLog` entries.
- A `Message` **belongs to** a sender `User` and a recipient `User`, and optionally to a parent `Message` (self-referential reply thread). Note: the `User` model has no inverse relationship methods for messages — message queries go one-directional, `Message → User`.

### Historical note
A `booking_requests` table (with `pending`/`approved`/`rejected`/`expired` status and an `expires_at` column) existed early in the project's history to support an approval-then-expiry booking flow. It was deliberately dropped in a later migration — the migration's own comment states the new direct-confirm model "completely replaces the request-awaiting-approval flow." It no longer exists in the schema or codebase; there is no request/approval step in booking today.

---

## 5. Routes & Pages by Role

All routes as declared in `routes/web.php` and `routes/auth.php`, verified line-by-line against the actual route files and their controller implementations.

### Public / guest
| Method | URI | Name | Description |
|---|---|---|---|
| GET | `/` | `home` | Landing page with role picker leading into registration/login |
| GET | `/about` | `about` | Static informational page |
| GET | `/locale/{locale}` | `locale.switch` | Switches the session's active language (ar/en) |
| GET/POST | `/register` | `register` | Registration for `role=student` or `role=staff` only; identifier checked against the `university_records` whitelist |
| GET/POST | `/login` | `login` | Login by email or identifier; blocks deactivated accounts |
| GET/POST | `/forgot-password`, `/reset-password/{token}` | `password.*` | Standard Breeze password reset flow |
| GET/POST | `/verify-email...` | `verification.*` | Standard Breeze email verification flow (scaffolded but **not enforced** — see §7) |

### All authenticated users
| Method | URI | Name | Description |
|---|---|---|---|
| GET | `/dashboard` | `dashboard` | Redirects to the caller's role-specific dashboard |
| POST | `/notifications/{notification}/read` | `notifications.read` | Marks one notification read, returns updated unread count as JSON |
| POST | `/notifications/mark-all-read` | `notifications.read-all` | Marks all of the user's notifications read |
| POST | `/logout` | `logout` | Ends the session |

### Student & Staff (`role:student,staff` / individual role middleware)
| Method | URI | Name | Description |
|---|---|---|---|
| GET | `/dashboard/student`, `/dashboard/staff` | `dashboard.student` / `dashboard.staff` | Recent bookings + current active booking |
| GET | `/booking` | `booking.index` | 3-day slot grid, or the "you have an active booking" screen if one exists |
| POST | `/booking` | `booking.store` | Creates a confirmed booking under transactional concurrency control (§6) |
| DELETE | `/booking/{booking}` | `booking.destroy` | Cancels the caller's own booking (403 if it belongs to someone else) |
| GET | `/my-medications` | `medications.mine` | Read-only history of the user's visit reports/prescriptions |
| GET | `/contact` | `contact` | Form to message a doctor |
| POST | `/contact` | `contact.store` | Sends the message and notifies the chosen doctor |

### Doctor (`role:doctor`)
| Method | URI | Name | Description |
|---|---|---|---|
| GET | `/dashboard/doctor` | `dashboard.doctor` | 3-day board of all confirmed bookings clinic-wide, active medication list, today's attendance |
| POST | `/doctor/bookings/{booking}/cancel` | `doctor.bookings.cancel` | Cancels a patient's confirmed booking and notifies them |
| POST | `/doctor/bookings/{booking}/report` | `doctor.bookings.report.store` | Creates/edits the visit report for a booking; adjusts medication stock (§6) |
| POST | `/doctor/attendance/check-out` | `doctor.attendance.checkout` | Manual clinic check-out (check-in is automatic on login) |
| POST | `/messages/{message}/reply` | `messages.reply` | Replies to a patient's contact message; returns JSON for the notification panel |

### Admin (`role:admin`, prefix `/admin`)
| Method | URI | Name | Description |
|---|---|---|---|
| GET | `/admin` | `admin.dashboard` | Stat tiles + charts |
| GET | `/admin/users` | `admin.users` | Paginated, searchable, role-filterable user list |
| POST | `/admin/users/{user}/toggle` | `admin.users.toggle` | Activates/deactivates an account (blocks disabling admins) |
| GET | `/admin/users/{user}/activity` | `admin.users.activity` | That user's activity history |
| GET | `/admin/doctors/create` | `admin.doctors.create` | New-doctor form |
| POST | `/admin/doctors` | `admin.doctors.store` | Creates a doctor account + weekly schedule |
| GET | `/admin/doctors/{doctor}/edit` | `admin.doctors.edit` | Edit-doctor form |
| PUT | `/admin/doctors/{doctor}` | `admin.doctors.update` | Updates doctor details/working days |
| GET | `/admin/records` | `admin.records` | Lists the identifier whitelist, filterable by type |
| POST | `/admin/records` | `admin.records.store` | Adds a valid student/staff identifier |
| DELETE | `/admin/records/{record}` | `admin.records.destroy` | Revokes an identifier |
| GET | `/admin/medications` | `admin.medications` | Paginated medication catalogue |
| POST | `/admin/medications` | `admin.medications.store` | Adds a new medication |
| PUT | `/admin/medications/{medication}` | `admin.medications.update` | Edits name/unit/low-stock threshold (not quantity) |
| POST | `/admin/medications/{medication}/restock` | `admin.medications.restock` | Adds stock quantity |
| POST | `/admin/medications/{medication}/toggle` | `admin.medications.toggle` | Activates/deactivates a medication |
| GET | `/admin/attendance` | `admin.attendance` | Daily roster: scheduled vs. checked-in doctors, tomorrow's on-duty list |
| GET | `/admin/activity-log` | `admin.activity-log` | Admin-scoped activity log |

Role gating is enforced server-side by `EnsureUserHasRole` middleware (`app/Http/Middleware/EnsureUserHasRole.php`), aliased as `role:` and applied per route group; an unauthorized role receives a 403.

---

## 6. Architecture Notes

### Booking concurrency control
This is the most carefully engineered part of the codebase. Each clinic hour (08:00–16:00) is divided into 5-minute slots — 9 per hour reserved for students (`:00` through `:40`) and 3 per hour reserved for staff (`:45`, `:50`, `:55`). Bookings are written directly as `confirmed`; there is no pending/expiring reservation state (an earlier request-and-approve flow was built and then deliberately removed — see §4).

`BookingController::store()` defends against double-booking with four layered mechanisms, all inside a single database transaction:
1. The calling user's own `users` row is locked (`lockForUpdate()`), serializing two near-simultaneous requests from the same user (e.g. a double-click) before either proceeds.
2. The "one active booking per user" rule is re-checked under that same lock.
3. The target slot is checked for an existing confirmed booking, also under a row lock.
4. As a final backstop, a real database-level unique index on a computed `active_slot_key` column (format `"{date}|{hour}|{minute}"`, set to `null` for any non-confirmed row via a model `saving` hook so multiple cancelled bookings don't collide on the unique constraint) catches any race that slips past the application-level checks. The resulting `QueryException` is caught and converted into an ordinary "slot just taken" message.

A related, distinct rule governs staff-reserved slots: if a staff-only slot's start time has passed *today* without being booked, it opens up to students for the rest of that day — a same-day capacity-reclaim mechanism, not a timeout on an individual reservation. It never applies to future days.

The same locking discipline is reused in `VisitReportController::store()` for medication stock: the booking row, the visit report row, existing prescription rows, and every referenced medication row are all locked before old/new prescribed quantities are diffed, validated against available stock, and applied with `increment`/`decrement` — so editing an existing report correctly returns stock as well as consuming it.

### Design system (neumorphism)
The UI's soft-shadow visual language is a genuine, hand-built CSS system in `resources/css/app.css`, not a third-party library. Named utility classes (`.neu-raised`, `.neu-pressed`, `.neu-icon-btn`, `.neu-badge`, and others) are built on two CSS custom properties (`--neu-dark`, `--neu-light`) that are redefined under the dark-mode selector, so every shadow inverts correctly with the theme without needing per-component dark-mode overrides.

### Dark mode & internationalization
Dark mode reads a `localStorage` flag (falling back to the OS's `prefers-color-scheme`) via an inline script that runs before first paint, avoiding a flash of the wrong theme. It's a per-browser preference, not synced to the account.

Language switching is session-based (`SetLocale` middleware, registered globally on the `web` middleware group) with parallel Arabic/English translation files covering the application's 19 UI namespaces in both `lang/en` and `lang/ar` (Arabic additionally has a 20th, standalone `validation.php` with no English counterpart — see §7). The layout sets its `dir` attribute dynamically per locale, and Tailwind's logical-property utilities (`ms-`, `me-`, `text-start`, etc.) are used throughout so the layout genuinely mirrors in Arabic rather than just flipping displayed text.

### Activity log design
Rather than storing a pre-rendered sentence, `ActivityLog::record()` stores a JSON blob of a translation key plus parameters. The log is rendered through Laravel's `__()` translator at read time, so an entry logged while the system was in Arabic still displays correctly in English if an admin views it in English later.

### Automatic doctor attendance
Doctor check-in is not a manual action — a `Login` event listener (`RecordDoctorAttendanceOnLogin`) creates the day's attendance row the moment a doctor authenticates (covering both the login form and "remember me" cookie re-authentication in one place), with a unique-constraint-based guard against duplicate rows on concurrent logins. Check-out remains a deliberate manual action, backed by the 16:00 scheduled auto-checkout as a safety net.

---

## 7. Limitations & Simulated Components

Full disclosure of what is not real, not automatic, or not fully enforced — verified by direct code inspection rather than inferred.

**Simulated: university identity verification.** The `university_records` table that gates self-registration is explicitly described in the registration controller's own source comment (in Arabic) as verification against "the fake university database" (قاعدة بيانات الجامعة الوهمية). It is a local whitelist, seeded and managed manually through the admin panel — there is no integration with any real university registrar or student information system.

**No payment processing.** Booking price (`0.25`) and per-medication fee (`0.20`) exist purely as stored/displayed figures. There is no checkout flow, no card form, and no payment gateway dependency anywhere in `composer.json` or the codebase.

**Mail is not actually delivered in this environment.** `MAIL_MAILER=log` in `.env` means password-reset and email-verification messages are written to the application log, not sent. All five in-app notification types (appointment reminders, booking cancellations, visit report completion, contact messages, message replies) are implemented as database-only notifications by design — they were never intended to send email.

**Email verification is scaffolded but not enforced.** The Breeze verification routes exist, but no route or dashboard in the application requires `email_verified_at` to be set (the `verified` middleware is never applied). A user can access every dashboard without ever verifying their email.

**No real-time delivery.** There is no websocket/broadcasting infrastructure (`BROADCAST_CONNECTION=log`, no channels file, no Echo/Pusher/Reverb dependency). The notification bell and message thread are populated on page load and updated via on-demand `fetch()` calls triggered by user actions, not a live push.

**Bookings are not routed to a specific doctor.** The `bookings` table has no `doctor_id` column. Any doctor's dashboard shows every confirmed booking clinic-wide, and any doctor can write the visit report or cancel any booking — there is no per-doctor appointment assignment. Doctor working-day schedules exist for staffing/attendance purposes only and are not checked when a student or staff member books a slot; it's possible to book a day with no doctor rostered.

**Low-stock "alerting" is a passive UI indicator only.** `Medication::isLowStock()` is used only to color a table row and show a badge on the admin medications page. There is no notification, email, or scheduled check behind it, and it does not block prescribing a medication down to zero — only prescribing more than is currently in stock.

**Placeholder contact information.** The Contact page ships with an explicitly labeled placeholder phone number ("Temporary sample number") and a placeholder email address (`clinic@xxx.edu.jo`), and the About page location text reads "Near the main student building" — none of this is real clinic contact information.

**Minor known gaps.**
- Arabic-specific validation message overrides exist (`lang/ar/validation.php`) with no equivalent English override file, so English validation errors fall back to Laravel's default English strings rather than an app-authored version.
- Messages have no read/unread tracking column at the database level.
- The `User` model has no inverse Eloquent relationship for messages (queries go `Message → User` only, not `User → Message`).

---

*Sections above reflect the state of the repository at the time of writing. All schema, route, and controller details were confirmed by reading the corresponding source files directly; the "178 tests / 545 assertions, 0 failures" figure was confirmed by running `php artisan test`.*
