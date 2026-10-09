import Link from "next/link";
import { Button } from "@/components/ui/button";
import type { PaginationMeta } from "@/lib/api";

/** "Showing 11–20 of 42" with Previous/Next links that set ?page= in the URL. */
export function BookingsPagination({ meta }: { meta: PaginationMeta }) {
  const { current_page: page, last_page: lastPage, from, to, total } = meta;

  return (
    <nav aria-label="Pagination" className="flex items-center justify-between gap-4 pt-4">
      <p className="text-sm text-muted-foreground">
        {total === 0 ? "No bookings" : `Showing ${from}–${to} of ${total}`}
      </p>
      <div className="flex items-center gap-2">
        <PageButton page={page - 1} disabled={page <= 1}>
          Previous
        </PageButton>
        <span className="text-sm text-muted-foreground">
          Page {page} of {lastPage}
        </span>
        <PageButton page={page + 1} disabled={page >= lastPage}>
          Next
        </PageButton>
      </div>
    </nav>
  );
}

function PageButton({ page, disabled, children }: { page: number; disabled: boolean; children: React.ReactNode }) {
  // A disabled link isn't possible, so render a plain disabled button at either end.
  if (disabled) {
    return (
      <Button variant="outline" size="sm" disabled>
        {children}
      </Button>
    );
  }

  return (
    <Button asChild variant="outline" size="sm">
      <Link href={`/bookings?page=${page}`}>{children}</Link>
    </Button>
  );
}
