// Conversions between the API's UTC ISO strings and the browser's local time.
// The API stores and returns UTC; <input type="datetime-local"> works in local time with no zone.

const pad = (n: number) => String(n).padStart(2, "0");

/**
 * "2030-01-15T02:00:00Z" -> "2030-01-15T10:00" when the browser is in UTC+8.
 * Produces the YYYY-MM-DDTHH:mm format that datetime-local inputs need.
 */
export function utcToLocalInput(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return "";

  return (
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
    `T${pad(date.getHours())}:${pad(date.getMinutes())}`
  );
}

/**
 * "2030-01-15T10:00" (local) -> "2030-01-15T02:00:00.000Z" when the browser is in UTC+8.
 * A date-time string without a zone is parsed as local time. Empty or invalid input is returned
 * unchanged so the API's validation reports it next to the field.
 */
export function localInputToUtc(value: string): string {
  if (!value) return value;

  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toISOString();
}

/** Human-readable local date and time for display, e.g. "15 Jan 2030, 10:00". */
export function formatDateTime(iso: string, locale?: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;

  return new Intl.DateTimeFormat(locale, { dateStyle: "medium", timeStyle: "short" }).format(date);
}
