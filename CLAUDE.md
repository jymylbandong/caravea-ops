# Project conventions

See `docs/plan.md` for scope, fields, endpoints, and tests.

## Stack
- Laravel API in `/api`, with SQLite and Pest
- Next.js App Router + TypeScript in `/web`

## Backend architecture

Follow this layering. Don't put business logic in controllers.

```
Controller → Service → Repository → Model
```

- **Controller:** thin. Receives a Form Request, calls the Service, returns an API Resource. No queries, no business rules.
- **Form Request:** all validation rules.
- **Service:** business logic (e.g. default status, overlap check). Depends on the repository interface, not Eloquent directly.
- **Repository:** all Eloquent queries. Define `BookingRepositoryInterface` and bind it in a service provider.
- **Model:** casts (status enum, datetimes) and fillable fields.
- **API Resource:** shapes the JSON response.

Example flow for create:

```
BookingController@store(StoreBookingRequest)
  → BookingService::create($validated)
    → BookingRepository::create($data)
  → return new BookingResource($booking)
```

## Frontend
- UI built with **shadcn/ui** and Tailwind CSS. Use shadcn components (Button, Input, Label, Textarea, Select, Table, Badge, Card, AlertDialog) instead of custom-styled elements.
- shadcn components live in `/web/components/ui`; don't edit them unless needed. Put app components in `/web/components`.
- Use AlertDialog to confirm deletes, and Badge to show booking status.
- One typed API client in `/web/lib/api.ts`
- Show Laravel 422 validation errors next to each form field
- Keep components small; no other UI libraries unless asked

## Working rules
- Make small, focused changes, one step at a time
- Write or update Pest tests with every backend change
- Don't add packages without asking first
- Explain any non-obvious code
