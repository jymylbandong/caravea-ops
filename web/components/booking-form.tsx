"use client";

import { Loader2 } from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { ErrorAlert, errorMessage } from "@/components/error-alert";
import { FormField } from "@/components/form-field";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import {
  ApiValidationError,
  BOOKING_STATUSES,
  createBooking,
  updateBooking,
  type Booking,
  type BookingStatus,
  type UpdateBookingInput,
  type ValidationErrors,
} from "@/lib/api";
import { localInputToUtc, utcToLocalInput } from "@/lib/datetime";

// Every input holds a string; values are converted to API types on submit.
interface FormValues {
  customer_name: string;
  customer_email: string;
  resource: string;
  starts_at: string; // local "YYYY-MM-DDTHH:mm"
  ends_at: string;
  guests: string;
  status: BookingStatus;
  notes: string;
}

type TextField = Exclude<keyof FormValues, "status">;

function initialValues(booking?: Booking): FormValues {
  return {
    customer_name: booking?.customer_name ?? "",
    customer_email: booking?.customer_email ?? "",
    resource: booking?.resource ?? "",
    starts_at: booking ? utcToLocalInput(booking.starts_at) : "",
    ends_at: booking ? utcToLocalInput(booking.ends_at) : "",
    guests: booking ? String(booking.guests) : "",
    status: booking?.status ?? "pending",
    notes: booking?.notes ?? "",
  };
}

function toPayload(values: FormValues): UpdateBookingInput {
  return {
    customer_name: values.customer_name,
    customer_email: values.customer_email,
    resource: values.resource,
    starts_at: localInputToUtc(values.starts_at),
    ends_at: localInputToUtc(values.ends_at),
    // NaN is serialised as null in JSON, so an empty field gets Laravel's "required" message
    // rather than "must be between 1 and 20".
    guests: values.guests.trim() === "" ? Number.NaN : Number(values.guests),
    status: values.status,
    notes: values.notes.trim() === "" ? null : values.notes,
  };
}

/** Create form when `booking` is omitted, edit form (full PUT) when it is given. */
export function BookingForm({ booking }: { booking?: Booking }) {
  const router = useRouter();
  const [values, setValues] = useState(() => initialValues(booking));
  const [errors, setErrors] = useState<ValidationErrors>({});
  const [formError, setFormError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  function update(field: TextField) {
    return (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
      setValues((current) => ({ ...current, [field]: event.target.value }));
  }

  // Props shared by every text input: value binding, error state, and accessibility wiring.
  function fieldProps(field: TextField) {
    const invalid = Boolean(errors[field]?.length);
    return {
      id: field,
      name: field,
      value: values[field],
      onChange: update(field),
      "aria-invalid": invalid || undefined,
      "aria-describedby": invalid ? `${field}-error` : undefined,
    };
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSubmitting(true);
    setErrors({});
    setFormError(null);

    try {
      const payload = toPayload(values);
      if (booking) {
        await updateBooking(booking.id, payload);
      } else {
        await createBooking(payload);
      }
      router.push("/bookings");
    } catch (e) {
      if (e instanceof ApiValidationError) {
        setErrors(e.errors);
      } else {
        setFormError(errorMessage(e));
      }
      setSubmitting(false);
    }
  }

  return (
    // noValidate: let Laravel validate, so its messages appear next to each field.
    <form onSubmit={handleSubmit} noValidate className="grid gap-6">
      {formError && <ErrorAlert title="Could not save the booking" message={formError} />}

      {/* Disabling the fieldset disables every input inside it while the request is in flight. */}
      <fieldset disabled={submitting} className="grid gap-6">
        <div className="grid gap-6 sm:grid-cols-2">
          <FormField id="customer_name" label="Customer name" errors={errors.customer_name}>
            <Input {...fieldProps("customer_name")} autoComplete="name" />
          </FormField>

          <FormField id="customer_email" label="Customer email" errors={errors.customer_email}>
            <Input {...fieldProps("customer_email")} type="email" autoComplete="email" />
          </FormField>

          <FormField id="resource" label="Resource" errors={errors.resource}>
            <Input {...fieldProps("resource")} placeholder="Meeting Room A" />
          </FormField>

          <FormField id="guests" label="Guests" errors={errors.guests}>
            <Input {...fieldProps("guests")} type="number" min={1} max={20} step={1} />
          </FormField>

          <FormField id="starts_at" label="Starts" errors={errors.starts_at}>
            <Input {...fieldProps("starts_at")} type="datetime-local" />
          </FormField>

          <FormField id="ends_at" label="Ends" errors={errors.ends_at}>
            <Input {...fieldProps("ends_at")} type="datetime-local" />
          </FormField>

          <FormField id="status" label="Status" errors={errors.status}>
            <Select
              value={values.status}
              onValueChange={(status) => setValues((current) => ({ ...current, status: status as BookingStatus }))}
            >
              <SelectTrigger
                id="status"
                className="w-full capitalize"
                aria-invalid={Boolean(errors.status?.length) || undefined}
              >
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                {BOOKING_STATUSES.map((status) => (
                  <SelectItem key={status} value={status} className="capitalize">
                    {status}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </FormField>
        </div>

        <FormField id="notes" label="Notes (optional)" errors={errors.notes}>
          <Textarea {...fieldProps("notes")} rows={4} />
        </FormField>
      </fieldset>

      <div className="flex justify-end gap-2">
        <Button asChild variant="outline">
          <Link href="/bookings">Cancel</Link>
        </Button>
        <Button type="submit" disabled={submitting}>
          {submitting && <Loader2 className="animate-spin" />}
          {submitting ? "Saving…" : booking ? "Save changes" : "Create booking"}
        </Button>
      </div>
    </form>
  );
}
