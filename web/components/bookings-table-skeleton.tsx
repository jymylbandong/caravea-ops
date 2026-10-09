import { Skeleton } from "@/components/ui/skeleton";
import { TableCell, TableRow } from "@/components/ui/table";

/** Placeholder rows shaped like real booking rows, shown while the first page loads. */
export function BookingsTableSkeletonRows({ rows = 5 }: { rows?: number }) {
  return Array.from({ length: rows }, (_, i) => (
    <TableRow key={i} aria-hidden>
      <TableCell>
        <Skeleton className="mb-1.5 h-4 w-32" />
        <Skeleton className="h-4 w-44" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-4 w-28" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-4 w-36" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-4 w-36" />
      </TableCell>
      <TableCell>
        <Skeleton className="ml-auto h-4 w-6" />
      </TableCell>
      <TableCell>
        <Skeleton className="h-5 w-16 rounded-full" />
      </TableCell>
      <TableCell>
        <div className="flex justify-end gap-2">
          <Skeleton className="h-7 w-12" />
          <Skeleton className="h-7 w-16" />
        </div>
      </TableCell>
    </TableRow>
  ));
}
