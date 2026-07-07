# Textbook Sale and Rental System

A peer-to-peer campus marketplace for buying, selling, and renting textbooks. Built for **XAMPP** (PHP + MySQL/MariaDB).

## Features

| Requirement | Implementation |
| --- | --- |
| User authentication | University email registration & login (`users` table) |
| Email verification | Verify link/token on register + resend from profile (dev: link shown on screen) |
| Password reset | Forgot/reset flow with time-limited tokens (`password_resets`) |
| Upload section | Sellers upload books with course codes **and cover images** |
| Course code matching | Browse and filter by course code (paginated) |
| Payment method | Cash, Mobile Money, Debit Card (`payments` table) |
| Realtime status tracker | Live polling for available / reserved / sold / rented (`assets/js/app.js`) |
| Waitlist queue | Join waitlist when books are unavailable |
| QR Code verification | QR tokens generated on each transaction |
| Rental timer | Duration picker, return date calculation, reminders |
| Messaging | In-app "Message Seller" conversations (`messages`) |
| Reviews & ratings | Star ratings + comments per book (`reviews`) |
| Seller summary | Earnings, completed deals, active listings on dashboard |
| Delete listings | Sellers delete own books; admin deletes books/users |
| CSRF protection | Synchronizer-token on all forms and state-changing actions |

## Setup (XAMPP)

1. **Start Apache and MySQL** in the XAMPP Control Panel.

2. **Create the database** — open phpMyAdmin (`http://localhost/phpmyadmin`) and:
   - Create database `textbooks` (or import will create it)
   - Import **`textbooks.sql`**
   - Import **`schema_extensions.sql`**
   - Import **`seed_data.sql`** (optional sample data)

    Or via command line:
    ```bash
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS textbooks;"
    mysql -u root textbooks < textbooks.sql
    mysql -u root textbooks < schema_extensions.sql
    mysql -u root textbooks < schema_extensions_v2.sql
    mysql -u root textbooks < schema_v3.sql
    mysql -u root textbooks < seed_data.sql
    ```

    > **Existing database?** If you already imported the earlier schema, just run
    > `schema_v3.sql` (and `schema_extensions_v2.sql` if missing) to add cover
    > images, email verification, password resets, reviews, and messaging.

3. **Configure** — edit `config/config.php` if needed:
   - `UNIVERSITY_EMAIL_DOMAIN` — your institution's email domain (default: `university.ac.ke`)
   - Database credentials (default XAMPP: root, no password)

4. **Open the app**:
   ```
   http://localhost/Academics%20and%20textbooks/
   ```

## Sample Login (after seed data)

| Email | Password | Role |
|---|---|---|
| mark@university.ac.ke | password | Seller |
| jane@university.ac.ke | password | Buyer |

## Project Structure

```
├── config/           App & database configuration
├── includes/         Shared PHP (auth, layout, helpers)
├── books/            Browse, view, upload
├── waitlist/         Waitlist actions
├── api/              Realtime status API
├── assets/           CSS & JavaScript
├── index.php         Home page
├── login.php         Authentication
├── register.php      University email signup
├── dashboard.php     User dashboard & rental timer
├── purchase.php      Buy / rent with payment
├── receipt.php       QR code receipt
└── verify.php        QR verification
```

## Database

Uses your original **`textbooks.sql`** schema plus **`schema_extensions.sql`** for:
- Book–seller linking and listing types
- Extended book status (available, reserved, sold, rented)
- Waitlist, rentals, and QR token tables

---

**Author:** MUWANDO MARK ALVIN — Academics and Textbooks Project
