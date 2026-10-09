import { Badge } from "@/components/ui/badge";
import type { BookingStatus } from "@/lib/api";

const variants = {
  pending: "secondary",
  confirmed: "default",
  cancelled: "destructive",
} as const satisfies Record<BookingStatus, "secondary" | "default" | "destructive">;

export function BookingStatusBadge({ status }: { status: BookingStatus }) {
  return (
    <Badge variant={variants[status]} className="capitalize">
      {status}
    </Badge>
  );
}
