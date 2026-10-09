"use client";

import { Loader2 } from "lucide-react";
import { useState } from "react";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { ErrorAlert, errorMessage } from "@/components/error-alert";
import { Button } from "@/components/ui/button";
import { deleteBooking, type Booking } from "@/lib/api";

interface Props {
  booking: Booking;
  onDeleted: (id: number) => void;
}

export function DeleteBookingButton({ booking, onDeleted }: Props) {
  const [open, setOpen] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleDelete(event: React.MouseEvent) {
    // AlertDialogAction closes the dialog on click; keep it open until the request finishes.
    event.preventDefault();
    setDeleting(true);
    setError(null);

    try {
      await deleteBooking(booking.id);
      setOpen(false);
      onDeleted(booking.id);
    } catch (e) {
      setError(errorMessage(e, "Could not delete the booking."));
    } finally {
      setDeleting(false);
    }
  }

  return (
    <AlertDialog
      open={open}
      onOpenChange={(next) => {
        setOpen(next);
        if (!next) setError(null);
      }}
    >
      <AlertDialogTrigger asChild>
        <Button variant="destructive" size="sm">
          Delete
        </Button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete this booking?</AlertDialogTitle>
          <AlertDialogDescription>
            {booking.customer_name}&apos;s booking for {booking.resource} will be permanently deleted.
          </AlertDialogDescription>
        </AlertDialogHeader>
        {error && <ErrorAlert title="Could not delete the booking" message={error} />}
        <AlertDialogFooter>
          <AlertDialogCancel disabled={deleting}>Cancel</AlertDialogCancel>
          <AlertDialogAction variant="destructive" disabled={deleting} onClick={handleDelete}>
            {deleting && <Loader2 className="animate-spin" />}
            {deleting ? "Deleting…" : "Delete"}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
