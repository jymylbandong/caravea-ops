# Bookings Module — Project Plan

A small bookings module with full CRUD, built with a Laravel API and a Next.js frontend.

**Time limit:** 4 hours

---

## 1. Scope

### Must have
- [x] **Laravel API:** full CRUD for bookings
- [x] **Validation** with Form Requests
- [x] **SQLite** database, so it runs with no setup
- [x] **Next.js frontend:** list, create, edit, delete
- [x] **Pest tests** for the API
- [x] **README**

### Nice to have (if time allows)
- [x] No overlapping bookings for the same resource, with a test
- [x] Pagination
- [ ] Loading and error states in the frontend

---

## 2. Data model

| Field | Type | Rule |
|---|---|---|
| `customer_name` | string | required, max 255 |
| `customer_email` | string | required, valid email |
| `resource` | string | required, e.g. "Meeting Room A" |
| `starts_at` | datetime | required, in the future (on create) |
| `ends_at` | datetime | required, after `starts_at` |
| `guests` | integer | required, 1–20 |
| `status` | enum | `pending`, `confirmed`, `cancelled`; defaults to `pending` |
| `notes` | text | optional |

**Design choices**
- Status as a PHP enum (`BookingStatus`), cast on the model
- Datetimes stored in UTC, converted in the frontend
- `starts_at` must be in the future only on create, so existing bookings can still be updated or cancelled

**Overlap rule (nice to have)**
Two bookings for the same resource overlap when
`new.starts_at < existing.ends_at AND new.ends_at > existing.starts_at`.
Cancelled bookings are ignored.

---

## 3. API endpoints

```
GET    /api/bookings          list
POST   /api/bookings          create
GET    /api/bookings/{id}     show
PUT    /api/bookings/{id}     update
DELETE /api/bookings/{id}     delete
```

---

## 4. Tests (Pest)

- [x] Creates a booking with valid data
- [x] Rejects `ends_at` before `starts_at`
- [x] Rejects invalid email and out-of-range guests
- [x] Updates a booking
- [x] Deletes a booking
- [x] (Nice to have) Rejects an overlapping booking for the same resource

---

## 5. Frontend pages

- [x] `/bookings`: list, with status badge
- [x] `/bookings/new`: create form
- [x] `/bookings/[id]/edit`: edit form
- [x] Delete from the list, with confirmation

**UI:** shadcn/ui with Tailwind CSS (Table for the list, Badge for status, AlertDialog for delete confirmation, form inputs for create and edit).

---

## 6. Project structure

```
/api        Laravel REST API (SQLite, Pest)
/web        Next.js App Router frontend (TypeScript, Tailwind, shadcn/ui)
/docs       Project plan
CLAUDE.md   Conventions for AI-assisted development
AI_NOTES.md AI usage and corrections log
README.md
```

---

## 7. Timeline

| Time | Task |
|---|---|
| 0:00–0:20 | Repo setup, plan, CLAUDE.md, first commit |
| 0:20–1:45 | Laravel: migration, model, enum, Form Requests, repository, service, controller, routes, tests |
| 1:45–3:15 | Next.js: API client, list page, create/edit forms, delete |
| 3:15–3:45 | README, cleanup, final test run |
| 3:45–4:00 | Buffer |

If time runs short, frontend polish is cut first. Tests are not cut.
