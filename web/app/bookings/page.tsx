import type { Metadata } from "next";
import Link from "next/link";
import { Suspense } from "react";
import { BookingsTable } from "@/components/bookings-table";
import { Button } from "@/components/ui/button";
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export const metadata: Metadata = {
  title: "Bookings",
};

export default function BookingsPage() {
  return (
    <main className="mx-auto w-full max-w-6xl p-6">
      <Card>
        <CardHeader>
          <CardTitle>Bookings</CardTitle>
          <CardDescription>Times are shown in your local timezone.</CardDescription>
          <CardAction>
            <Button asChild>
              <Link href="/bookings/new">New booking</Link>
            </Button>
          </CardAction>
        </CardHeader>
        <CardContent>
          {/* The table reads ?page= with useSearchParams, which needs a Suspense boundary under cacheComponents. */}
          <Suspense fallback={<p className="text-sm text-muted-foreground">Loading bookings…</p>}>
            <BookingsTable />
          </Suspense>
        </CardContent>
      </Card>
    </main>
  );
}
