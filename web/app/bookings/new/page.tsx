import type { Metadata } from "next";
import { BookingForm } from "@/components/booking-form";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

export const metadata: Metadata = {
  title: "New booking",
};

export default function NewBookingPage() {
  return (
    <main className="mx-auto w-full max-w-3xl p-6">
      <Card>
        <CardHeader>
          <CardTitle>New booking</CardTitle>
          <CardDescription>Times are in your local timezone.</CardDescription>
        </CardHeader>
        <CardContent>
          <BookingForm />
        </CardContent>
      </Card>
    </main>
  );
}
