import { Skeleton } from "@/components/ui/skeleton";

/** Placeholder shaped like BookingForm, shown while the booking to edit loads. */
export function BookingFormSkeleton() {
  return (
    <div className="grid gap-6" aria-busy aria-label="Loading booking">
      <div className="grid gap-6 sm:grid-cols-2">
        {Array.from({ length: 7 }, (_, i) => (
          <div key={i} className="grid gap-2">
            <Skeleton className="h-4 w-24" />
            <Skeleton className="h-8 w-full" />
          </div>
        ))}
      </div>
      <div className="grid gap-2">
        <Skeleton className="h-4 w-28" />
        <Skeleton className="h-24 w-full" />
      </div>
      <div className="flex justify-end gap-2">
        <Skeleton className="h-8 w-20" />
        <Skeleton className="h-8 w-32" />
      </div>
    </div>
  );
}
