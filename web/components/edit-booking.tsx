"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { BookingForm } from "@/components/booking-form";
import { BookingFormSkeleton } from "@/components/booking-form-skeleton";
import { ErrorAlert, errorMessage } from "@/components/error-alert";
import { Button } from "@/components/ui/button";
import { ApiError, getBooking, type Booking } from "@/lib/api";

type LoadError = { kind: "not-found" } | { kind: "failed"; message: string };

/** Loads the booking from the URL's [id] and renders the edit form for it. */
export function EditBooking() {
  const { id } = useParams<{ id: string }>();
  const [booking, setBooking] = useState<Booking | null>(null);
  const [error, setError] = useState<LoadError | null>(null);
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    let cancelled = false;

    getBooking(id)
      .then((loaded) => {
        if (cancelled) return;
        setBooking(loaded);
        setError(null);
      })
      .catch((e: unknown) => {
        if (cancelled) return;
        setError(
          e instanceof ApiError && e.status === 404
            ? { kind: "not-found" }
            : { kind: "failed", message: errorMessage(e, "Could not load the booking.") },
        );
      });

    return () => {
      cancelled = true;
    };
  }, [id, reloadKey]);

  const backLink = (
    <Button asChild variant="outline" size="sm">
      <Link href="/bookings">Back to bookings</Link>
    </Button>
  );

  if (error?.kind === "not-found") {
    return (
      <ErrorAlert title="Booking not found" message="It may have been deleted.">
        {backLink}
      </ErrorAlert>
    );
  }

  if (error) {
    return (
      <ErrorAlert
        title="Could not load the booking"
        message={error.message}
        onRetry={() => {
          setError(null);
          setReloadKey((key) => key + 1);
        }}
      >
        {backLink}
      </ErrorAlert>
    );
  }

  if (!booking) {
    return <BookingFormSkeleton />;
  }

  // This page also stays mounted while hidden and refetches when shown again. Keying on updated_at
  // remounts the form with fresh values when the booking has changed since the form was filled in.
  return <BookingForm key={booking.updated_at} booking={booking} />;
}
