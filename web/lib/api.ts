// Typed client for the Laravel bookings API. All requests to the backend go through here.

export type BookingStatus = "pending" | "confirmed" | "cancelled";

export const BOOKING_STATUSES: readonly BookingStatus[] = ["pending", "confirmed", "cancelled"];

/** A booking as returned by the API. Datetimes are UTC ISO 8601 strings, e.g. "2030-01-15T10:00:00Z". */
export interface Booking {
  id: number;
  customer_name: string;
  customer_email: string;
  resource: string;
  starts_at: string;
  ends_at: string;
  guests: number;
  status: BookingStatus;
  notes: string | null;
  created_at: string | null;
  updated_at: string | null;
}

/** Payload for POST /bookings. Status is optional; the API defaults it to "pending". */
export interface CreateBookingInput {
  customer_name: string;
  customer_email: string;
  resource: string;
  starts_at: string;
  ends_at: string;
  guests: number;
  status?: BookingStatus;
  notes?: string | null;
}

/** Payload for PUT /bookings/{id}. A full replacement, so status is required. */
export interface UpdateBookingInput extends CreateBookingInput {
  status: BookingStatus;
}

/** Laravel's pagination "meta" (only the fields the frontend uses). */
export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null; // 1-based position of the first item on this page; null when the page is empty
  to: number | null;
}

/** A paginated API Resource collection. */
export interface Paginated<T> {
  data: T[];
  meta: PaginationMeta;
}

/** Laravel's 422 "errors" object: field name to list of messages. */
export type ValidationErrors = Partial<Record<keyof CreateBookingInput, string[]>> &
  Record<string, string[] | undefined>;

/** Any non-2xx response from the API. */
export class ApiError extends Error {
  readonly status: number;

  constructor(message: string, status: number) {
    super(message);
    this.name = "ApiError";
    this.status = status;
  }
}

/** A 422 response; `errors` holds the per-field messages to show next to each input. */
export class ApiValidationError extends ApiError {
  readonly errors: ValidationErrors;

  constructor(message: string, errors: ValidationErrors) {
    super(message, 422);
    this.name = "ApiValidationError";
    this.errors = errors;
  }
}

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_URL}${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      ...(init.body ? { "Content-Type": "application/json" } : {}),
      ...init.headers,
    },
    // Bookings change often; never serve a cached response.
    cache: "no-store",
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const body = await response.json().catch(() => null);

  if (response.status === 422) {
    throw new ApiValidationError(body?.message ?? "The given data was invalid.", body?.errors ?? {});
  }

  if (!response.ok) {
    throw new ApiError(body?.message || response.statusText || "Request failed", response.status);
  }

  return body as T;
}

// Laravel API Resources wrap single items and collections in { data: ... }.
type Wrapped<T> = { data: T };

export async function listBookings({ page = 1, perPage }: { page?: number; perPage?: number } = {}): Promise<
  Paginated<Booking>
> {
  const params = new URLSearchParams({ page: String(page) });
  if (perPage) params.set("per_page", String(perPage));

  return request<Paginated<Booking>>(`/bookings?${params}`);
}

export async function getBooking(id: number | string): Promise<Booking> {
  return (await request<Wrapped<Booking>>(`/bookings/${id}`)).data;
}

export async function createBooking(input: CreateBookingInput): Promise<Booking> {
  const result = await request<Wrapped<Booking>>("/bookings", {
    method: "POST",
    body: JSON.stringify(input),
  });
  return result.data;
}

export async function updateBooking(id: number | string, input: UpdateBookingInput): Promise<Booking> {
  const result = await request<Wrapped<Booking>>(`/bookings/${id}`, {
    method: "PUT",
    body: JSON.stringify(input),
  });
  return result.data;
}

export async function deleteBooking(id: number | string): Promise<void> {
  await request<void>(`/bookings/${id}`, { method: "DELETE" });
}
