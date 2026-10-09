import type { Metadata } from "next";
import { Suspense } from "react";
import { EditBooking } from "@/components/edit-booking";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export const metadata: Metadata = {
  title: "Edit booking",
};

export default function EditBookingPage() {
  return (
    <main className="mx-auto w-full max-w-3xl p-6">
      <Card>
        <CardHeader>
          <CardTitle>Edit booking</CardTitle>
          <CardDescription>Times are in your local timezone.</CardDescription>
        </CardHeader>
        <CardContent>
          {/* The [id] is only known at request time; with cacheComponents, useParams needs a Suspense boundary. */}
          <Suspense fallback={<p className="text-sm text-muted-foreground">Loading booking…</p>}>
            <EditBooking />
          </Suspense>
        </CardContent>
      </Card>
    </main>
  );
}
