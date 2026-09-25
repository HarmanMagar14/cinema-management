# CineMax

A cinema booking web app built with Laravel 12. Customers browse movies, pick seats, pay through PayMongo and get QR-code tickets by email. Admins manage movies, showtimes, cinemas, halls, users and bookings, and see sales analytics.

## Features

**Customers**
- Browse Now Showing and Coming Soon movies, filter by genre, watch trailers and read reviews
- Pick seats on a live seat map. Booked seats are locked until the screening ends.
- Pay by card or GCash through PayMongo checkout
- QR-code tickets on the booking page, plus a confirmation email
- Email verification with a 6-digit code (OTP) when registering
- Profile with booking history and avatar

**Admins** (`/admin/dashboard`)
- Dashboard and analytics: revenue, bookings, tickets sold, occupancy, top movies, genres, payment methods, user growth (7 / 30 / 90 / 365 days)
- Movies in two steps: details and poster first, then showtimes. Hall clashes are detected automatically.
- Showtimes that already have bookings can't be removed or moved to another hall
- Cinemas and halls. Seats are generated from hall capacity.
- Users (roles and status) and bookings (change status)

## Requirements

- PHP 8.2+ with the `zip` extension enabled for Composer (XAMPP ships it; enable `extension=zip` in `php.ini`)
- MySQL / MariaDB (XAMPP)
- Composer
- Node.js 18+ and npm

## Setup

```bash
composer install
npm install
npm run build

cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
```

Create an empty database named `cinema_management1` (or change `DB_DATABASE` in `.env`), then:

```bash
php artisan migrate --seed                         # roles, admin + test customer, genres, cinemas, halls, seats, movies, showtimes
php artisan db:seed --class=DemoDataSeeder         # optional: 25 dummy customers + 12 movies with posters and upcoming showtimes
php artisan storage:link                           # serves uploaded posters and avatars
php artisan serve
```

Open http://127.0.0.1:8000. Use `php artisan serve`, not `http://localhost/pdcCinema`: Apache would serve the raw project folder instead of running the app.

On Windows with XAMPP, if `php` isn't recognized, add `C:\xampp\php` to your PATH or run `C:\xampp\php\php.exe artisan serve`.

### Logins

| Role | Email | Password |
|---|---|---|
| Admin | `admin@cinemax.com` | `password` |
| Customer | `customer@cinemax.com` | `password` |
| Dummy customers (DemoDataSeeder) | e.g. `maria.santos@example.com` | `password` |

## Configuration

### Email (Resend)

Verification codes and booking confirmations are sent with [Resend](https://resend.com). Composer installs the package.

```env
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="CineMax"
```

- Until you verify a domain at resend.com/domains, Resend only delivers to your own Resend account address, and the sender must be `onboarding@resend.dev`.
- With `APP_ENV=local`, a failed send doesn't block registration or login. The code is written to `storage/logs/laravel.log` instead.
- To send no email at all while developing, set `MAIL_MAILER=log`.

### Payments (PayMongo)

```env
PAYMONGO_API_KEY=pk_test_...
PAYMONGO_SECRET_KEY=sk_test_...        # the secret key, not the public one
PAYMONGO_WEBHOOK_SECRET=whsk_...       # only needed once the site is public
```

Get the keys from dashboard.paymongo.com → **Developers → API Keys**. Use test keys while developing. Test card: `4343 4343 4343 4345`, any future expiry, any CVC. For GCash, click **Authorize Test Payment**.

**How a payment is confirmed:**
1. **Pay Now** creates a PayMongo checkout session and sends the customer to it.
2. After paying, PayMongo sends the customer back to `/bookings/{id}/payment-success`. That page asks PayMongo whether the session was actually paid. Only then is the booking confirmed, tickets issued and the confirmation email sent.
3. When the site is public, register a webhook at `https://<your-domain>/webhooks/paymongo` for the `checkout_session.payment.paid` event. It confirms bookings even if the customer never returns to the site.

Steps 2 and 3 share the same confirmation step (`app/Services/BookingPaymentService.php`), so a booking is only confirmed once. Webhooks can't reach `127.0.0.1`, so step 3 is skipped in local development. If a booking is paid but still shows as pending, the booking page's **Check payment status** button runs step 2 again.

## Project structure

| Path | What's there |
|---|---|
| `routes/web.php` | All routes. Admin routes are in the `admin` group. |
| `app/Http/Controllers/AuthController.php` | Login, registration, OTP verification |
| `app/Http/Controllers/BookingsController.php` | Seat selection, booking, payment, cancellation |
| `app/Http/Controllers/PayMongoWebhookController.php` | PayMongo webhook |
| `app/Http/Controllers/Admin/AdminController.php` | Everything under `/admin` |
| `app/Services/PayMongoService.php` | PayMongo API calls and signature checks |
| `app/Services/BookingPaymentService.php` | Confirms a paid booking, issues tickets, sends the email |
| `database/seeders/` | `DatabaseSeeder` (base data), `MoviesSeeder`, `DemoDataSeeder` (dummy data and generated posters) |
| `resources/views/` | Blade templates. `admin/` holds the admin panel. |

## Posters

Posters are stored in `storage/app/public/posters/` and served from `/storage/posters/...`. A movie's `poster` column holds either that path or a full image URL. Movies without a poster show `public/images/no-poster.svg`.

`storage/app/public` is not committed to git, so a fresh clone has no poster images:
- `DemoDataSeeder` generates posters for its movies.
- For other movies, upload a poster from the admin panel (**Movies → Edit**).

## Known issues

- **UI polish**: some pages still need consistent spacing and better mobile layouts.
- **Changing a hall's capacity** regenerates its seats. Existing bookings for that hall lose their seat records, so avoid it for halls with bookings.
