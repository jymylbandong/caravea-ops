# Bookings Module

A small bookings module with full CRUD: a **Laravel 12** REST API in [`/api`](api) and a **Next.js 16** frontend in [`/web`](web).

- API: SQLite (no database server needed), Form Request validation, Pest tests
- Frontend: App Router + TypeScript, Tailwind CSS v4, shadcn/ui
- Scope, data model and timeline: [`docs/plan.md`](docs/plan.md)
- How AI was used, and where its output needed fixing: [`AI_NOTES.md`](AI_NOTES.md)

## Requirements

- PHP 8.2+ with the `pdo_sqlite` extension, and Composer
- Node.js 20.9+ and npm (developed on Node 22)

## Getting started

Run the API and the frontend in two terminals.

### 1. API (http://localhost:8000)

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan db:seed      # optional: 10 demo bookings
php artisan serve        # http://localhost:8000
```

### 2. Frontend (http://localhost:3000)

```bash
cd web
npm install
cp .env.example .env.local
npm run dev              # http://localhost:3000
```

Open http://localhost:3000. It redirects to `/bookings`.

### Configuration

| Variable | File | Default | Purpose |
|---|---|---|---|
| `FRONTEND_URL` | `api/.env` | `http://localhost:3000` | The only origin allowed by CORS |
| `NEXT_PUBLIC_API_URL` | `web/.env.local` | `http://localhost:8000/api` | Base URL the frontend calls |

## Tests

```bash
cd api && php artisan test   # Pest: feature + unit tests
cd web && npm test           # Node test runner: UTC/local datetime conversion
cd web && npm run lint && npm run build
```

API tests run against an in-memory SQLite database, so they don't touch `database.sqlite`.

## API

Base URL: `http://localhost:8000/api`. Send `Accept: application/json`, although `/api/*` errors are always returned as JSON.

| Method | Path | Description | Success |
|---|---|---|---|
| `GET` | `/bookings` | List all bookings, soonest `starts_at` first | `200` |
| `POST` | `/bookings` | Create a booking | `201` |
| `GET` | `/bookings/{id}` | Show one booking | `200` |
| `PUT` | `/bookings/{id}` | Replace a booking (full payload) | `200` |
| `DELETE` | `/bookings/{id}` | Delete a booking | `204` |

A missing or non-numeric `{id}` returns `404`. Validation failures return `422`.

### Fields and validation

| Field | Rules |
|---|---|
| `customer_name` | required, string, max 255 |
| `customer_email` | required, valid email, max 255 |
| `resource` | required, string, max 255 (e.g. `"Meeting Room A"`) |
| `starts_at` | required, datetime; **must be in the future on create only** |
| `ends_at` | required, datetime, after `starts_at` |
| `guests` | required, integer, 1–20 |
| `status` | `pending`, `confirmed` or `cancelled`. Optional on create (defaults to `pending`), required on update |
| `notes` | optional, nullable |

`PUT` is a full replacement, so every required field must be sent, including `status`.

**No double-booking:** a booking may not overlap another non-cancelled booking for the same `resource`. Two bookings overlap when `new.starts_at < existing.ends_at AND new.ends_at > existing.starts_at`, so back-to-back bookings (10:00–11:00 then 11:00–12:00) are allowed. A conflict returns `422` with the message on `starts_at`:

```json
{
  "message": "This resource is already booked for an overlapping time.",
  "errors": { "starts_at": ["This resource is already booked for an overlapping time."] }
}
```

### Example

```bash
curl -X POST http://localhost:8000/api/bookings \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{
    "customer_name": "Ada Lovelace",
    "customer_email": "ada@example.com",
    "resource": "Meeting Room A",
    "starts_at": "2030-01-15T10:00:00+08:00",
    "ends_at": "2030-01-15T11:00:00+08:00",
    "guests": 4
  }'
```

```json
{
  "data": {
    "id": 1,
    "customer_name": "Ada Lovelace",
    "customer_email": "ada@example.com",
    "resource": "Meeting Room A",
    "starts_at": "2030-01-15T02:00:00Z",
    "ends_at": "2030-01-15T03:00:00Z",
    "guests": 4,
    "status": "pending",
    "notes": null,
    "created_at": "2026-10-09T13:00:00Z",
    "updated_at": "2026-10-09T13:00:00Z"
  }
}
```

A `422` response has Laravel's standard shape, with messages per field:

```json
{
  "message": "The customer email field must be a valid email address. (and 1 more error)",
  "errors": {
    "customer_email": ["The customer email field must be a valid email address."],
    "guests": ["The guests field must be between 1 and 20."]
  }
}
```

## Architecture

### Backend

Business logic stays out of controllers (see [`CLAUDE.md`](CLAUDE.md)):

```
Controller → Service → Repository → Model
```

| Layer | File | Responsibility |
|---|---|---|
| Route | `routes/api.php` | `apiResource('bookings')` with a numeric `{id}` |
| Form Request | `app/Http/Requests/StoreBookingRequest.php`, `UpdateBookingRequest.php` | All validation rules; datetimes converted to UTC first |
| Controller | `app/Http/Controllers/Api/BookingController.php` | Thin: request → service → resource |
| Service | `app/Services/BookingService.php` | Business rules (default `pending` status, overlap check); depends on the repository interface |
| Repository | `app/Repositories/BookingRepository.php` | All Eloquent queries, including `hasOverlap()`; bound to `BookingRepositoryInterface` in `RepositoryServiceProvider` |
| Exception | `app/Exceptions/BookingOverlapException.php` | Thrown by the service on a conflict; renders as a `422` on `starts_at` |
| Model | `app/Models/Booking.php` | Fillable fields; casts `status` to the `BookingStatus` enum and the datetimes |
| Resource | `app/Http/Resources/BookingResource.php` | JSON shape; datetimes as UTC ISO 8601 |

Tests are split the same way. `tests/Unit/Services` tests the service against a mocked repository. `tests/Feature/Repositories` tests the queries. `tests/Feature/Api` tests the HTTP endpoints from end to end.

### Frontend

| Path | Purpose |
|---|---|
| `lib/api.ts` | The one typed API client. Throws `ApiValidationError` (with Laravel's `errors`) on `422` |
| `lib/datetime.ts` | UTC ↔ local conversion for display and for `datetime-local` inputs |
| `app/bookings/page.tsx` | List: Table, status Badge, delete with AlertDialog |
| `app/bookings/new/page.tsx` | Create form |
| `app/bookings/[id]/edit/page.tsx` | Edit form |
| `components/booking-form.tsx` | Shared create/edit form that shows `422` errors under each field |

## Design decisions

- **Datetimes are stored in UTC and converted in the browser.** The API returns `...Z` strings. The frontend shows them in local time and converts `datetime-local` input back to UTC before sending. The Form Requests also convert any incoming offset to UTC, because Eloquent's datetime cast would otherwise drop it: `10:00+08:00` would be stored as `10:00`, not `02:00`.
- **`starts_at` must be in the future only on create.** Bookings that have already started can still be edited or cancelled.
- **`PUT` is a full replacement.** `UpdateBookingRequest` reuses the create rules, except that `status` is required and `starts_at` may be in the past.
- **No route model binding.** The controller takes `int $id` and the service loads the booking through the repository, so every query stays in the repository layer. `ModelNotFoundException` becomes a JSON `404`.
- **The overlap rule is in the service, not the Form Request.** It is a business rule that needs a database query, so the service asks the repository (`hasOverlap`) and throws `BookingOverlapException`. That exception renders in the same `422` shape as validation errors, so the form shows it under **Starts** without frontend changes. Cancelled bookings never block a slot. Saving a booking as cancelled skips the check, and on update the booking is not compared with itself.
  - *Known limitation:* the check and the insert are not atomic, so two simultaneous requests could both pass. A database-level exclusion constraint would close this gap, but SQLite doesn't support one.
- **CORS allows only `FRONTEND_URL`.** Laravel's default is `*`.
- **The frontend loads data in the browser.** This Next 16 setup has `cacheComponents` enabled, so request-time data in Server Components would need extra Suspense or caching setup. Loading in the browser also formats times in the user's own timezone. The forms use `noValidate`, so the messages under each field come from Laravel rather than the browser.

## Scope

**Done (must-haves):** full CRUD API, Form Request validation, SQLite, Next.js list/create/edit/delete, Pest tests, this README.

**Done (nice-to-haves):** no overlapping bookings for the same resource, with tests at the service, repository and API levels.

**Not done (nice-to-haves):**
- No pagination.
- Loading and error states are basic: a loading row, an empty state and inline error messages.
