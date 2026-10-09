"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useCallback, useEffect, useState } from "react";
import { BookingStatusBadge } from "@/components/booking-status-badge";
import { BookingsPagination } from "@/components/bookings-pagination";
import { BookingsTableSkeletonRows } from "@/components/bookings-table-skeleton";
import { DeleteBookingButton } from "@/components/delete-booking-button";
import { ErrorAlert, errorMessage } from "@/components/error-alert";
import { Button } from "@/components/ui/button";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { cn } from "@/lib/utils";
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
  // Bumped by Retry and after a delete to refetch the same page.
  const [reloadKey, setReloadKey] = useState(0);

  useEffect(() => {
    let cancelled = false;

    listBookings({ page })
      .then((next) => {
        if (cancelled) return;
        setResult(next);
        setError(null);
      })
      .catch((e: unknown) => {
        if (!cancelled) setError(errorMessage(e, "Could not load bookings."));
      });

    // Ignore a slow response for a page the user has already navigated away from.
    return () => {
      cancelled = true;
    };
  }, [page, reloadKey]);

  const reload = useCallback(() => {
    setError(null);
    setReloadKey((key) => key + 1);
  }, []);

  function handleDeleted() {
    // Deleting the last row on a later page would leave it empty; step back a page instead.
    if (result && result.data.length === 1 && page > 1) {
      router.replace(`/bookings?page=${page - 1}`);
    } else {
      reload();
    }
  }

  // The first load has no data yet. Changing page keeps the old rows (dimmed) until the new ones arrive.
  const initialLoading = !result && !error;
  const changingPage = result !== null && result.meta.current_page !== page && !error;
  const bookings = result?.data;

  return (
    <div className="grid gap-4">
      {error && <ErrorAlert title="Could not load bookings" message={error} onRetry={reload} />}

      {(result || initialLoading) && (
        <div aria-busy={initialLoading || changingPage}>
          <Table className={cn("transition-opacity", changingPage && "opacity-50")}>
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
              {initialLoading && <BookingsTableSkeletonRows />}
              {bookings?.length === 0 && (
                <TableRow>
                  <TableCell colSpan={7} className="py-10 text-center">
                    <EmptyState page={page} total={result?.meta.total ?? 0} />
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
          {result && result.meta.total > 0 && <BookingsPagination meta={result.meta} />}
        </div>
      )}
    </div>
  );
}

function EmptyState({ page, total }: { page: number; total: number }) {
  // Bookings exist, but the URL points past the last page (e.g. ?page=99).
  if (total > 0) {
    return (
      <div className="grid justify-items-center gap-3">
        <p className="text-muted-foreground">There are no bookings on page {page}.</p>
        <Button asChild variant="outline" size="sm">
          <Link href="/bookings">Go to the first page</Link>
        </Button>
      </div>
    );
  }

  return (
    <div className="grid justify-items-center gap-3">
      <p className="text-muted-foreground">No bookings yet.</p>
      <Button asChild size="sm">
        <Link href="/bookings/new">Create the first booking</Link>
      </Button>
    </div>
  );
}
