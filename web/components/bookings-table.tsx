"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { BookingStatusBadge } from "@/components/booking-status-badge";
import { BookingsPagination } from "@/components/bookings-pagination";
import { DeleteBookingButton } from "@/components/delete-booking-button";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { listBookings, type Booking, type Paginated } from "@/lib/api";
import { formatDateTime } from "@/lib/datetime";

// Fetched in the browser so times are formatted in the user's local timezone.
export function BookingsTable() {
  const router = useRouter();
  const searchParams = useSearchParams();
  // Invalid or missing ?page= falls back to page 1.
  const page = Math.max(1, Math.floor(Number(searchParams.get("page"))) || 1);

  const [result, setResult] = useState<Paginated<Booking> | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    listBookings({ page })
      .then((next) => {
        setResult(next);
        setError(null);
      })
      .catch((e: unknown) => setError(e instanceof Error ? e.message : "Could not load bookings."));
  }, [page]);

  useEffect(load, [load]);

  function handleDeleted() {
    // Deleting the last row on a later page would leave it empty; step back a page instead.
    if (result && result.data.length === 1 && page > 1) {
      router.replace(`/bookings?page=${page - 1}`);
    } else {
      load();
    }
  }

  if (error) {
    return (
      <p role="alert" className="text-sm text-destructive">
        Could not load bookings: {error}
      </p>
    );
  }

  const bookings = result?.data;

  return (
    <>
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Customer</TableHead>
            <TableHead>Resource</TableHead>
            <TableHead>Starts</TableHead>
            <TableHead>Ends</TableHead>
            <TableHead className="text-right">Guests</TableHead>
            <TableHead>Status</TableHead>
            <TableHead className="text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {!bookings && (
            <TableRow>
              <TableCell colSpan={7} className="text-center text-muted-foreground">
                Loading bookings…
              </TableCell>
            </TableRow>
          )}
          {bookings?.length === 0 && (
            <TableRow>
              <TableCell colSpan={7} className="text-center text-muted-foreground">
                No bookings yet.
              </TableCell>
            </TableRow>
          )}
          {bookings?.map((booking) => (
            <TableRow key={booking.id}>
              <TableCell>
                <div className="font-medium">{booking.customer_name}</div>
                <div className="text-muted-foreground">{booking.customer_email}</div>
              </TableCell>
              <TableCell>{booking.resource}</TableCell>
              <TableCell>{formatDateTime(booking.starts_at)}</TableCell>
              <TableCell>{formatDateTime(booking.ends_at)}</TableCell>
              <TableCell className="text-right">{booking.guests}</TableCell>
              <TableCell>
                <BookingStatusBadge status={booking.status} />
              </TableCell>
              <TableCell>
                <div className="flex justify-end gap-2">
                  <Button asChild variant="outline" size="sm">
                    <Link href={`/bookings/${booking.id}/edit`}>Edit</Link>
                  </Button>
                  <DeleteBookingButton booking={booking} onDeleted={handleDeleted} />
                </div>
              </TableCell>
            </TableRow>
          ))}
        </TableBody>
      </Table>
      {result && <BookingsPagination meta={result.meta} />}
    </>
  );
}
