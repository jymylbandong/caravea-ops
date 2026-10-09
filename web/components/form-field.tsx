import { Label } from "@/components/ui/label";

interface Props {
  id: string;
  label: string;
  errors?: string[];
  children: React.ReactNode;
}

/** Label + input + the Laravel validation messages for that field. */
export function FormField({ id, label, errors, children }: Props) {
  return (
    <div className="grid gap-2">
      <Label htmlFor={id}>{label}</Label>
      {children}
      {errors?.length ? (
        <div id={`${id}-error`} className="grid gap-1 text-sm text-destructive">
          {errors.map((message) => (
            <p key={message}>{message}</p>
          ))}
        </div>
      ) : null}
    </div>
  );
}
