import { CircleAlert } from "lucide-react";
import { Alert, AlertAction, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";

interface Props {
  title: string;
  message?: string;
  onRetry?: () => void;
  children?: React.ReactNode; // extra actions, e.g. a "Back to bookings" link
}

/** A destructive Alert for failed requests, with an optional Retry button. */
export function ErrorAlert({ title, message, onRetry, children }: Props) {
  return (
    <Alert variant="destructive">
      <CircleAlert />
      <AlertTitle>{title}</AlertTitle>
      {message && <AlertDescription>{message}</AlertDescription>}
      {(onRetry || children) && (
        <AlertAction className="flex gap-2">
          {children}
          {onRetry && (
            <Button variant="outline" size="sm" onClick={onRetry}>
              Retry
            </Button>
          )}
        </AlertAction>
      )}
    </Alert>
  );
}

/** Human-readable message from anything a fetch can throw. */
export function errorMessage(error: unknown, fallback = "Something went wrong."): string {
  // fetch() rejects with a TypeError when the API is unreachable (server down, CORS, offline).
  if (error instanceof TypeError) return "Could not reach the API. Is the Laravel server running?";
  return error instanceof Error && error.message ? error.message : fallback;
}
