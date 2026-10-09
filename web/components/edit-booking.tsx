"use client";

import { useParams } from "next/navigation";
import { useEffect, useState } from "react";
import { BookingForm } from "@/components/booking-form";
import { ApiError, getBooking, type Booking } from "@/lib/api";

/** Loads the booking from the URL's [id] and renders the edit form for it. */
export function EditBooking() {
  const { id } = useParams<{ id: string }>();
  const [booking, setBooking] = useState<Booking | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    getBooking(id)
      .then(setBooking)
      .catch((e: unknown) => {
        if (e instanceof ApiError && e.status === 404) {
          setError("This booking does not exist.");
        } else {
          setError(e instanceof Error ? e.message : "Could not load the booking.");
        }
      });
  }, [id]);

  if (error) {
    return (
      <p role="alert" className="text-sm text-destructive">
        {error}
      </p>
    );
  }

  if (!booking) {
    return <p className="text-sm text-muted-foreground">Loading booking…</p>;
  }

  return <BookingForm booking={booking} />;
}
