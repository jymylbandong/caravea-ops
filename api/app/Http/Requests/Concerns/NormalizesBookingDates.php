<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Carbon;
use Throwable;

/**
 * Converts starts_at and ends_at to UTC before validation.
 *
 * Eloquent's datetime cast keeps the wall-clock time and drops any offset, so
 * "2030-01-15T10:00:00+08:00" would otherwise be stored as 10:00 instead of 02:00 UTC.
 */
trait NormalizesBookingDates
{
    protected function prepareForValidation(): void
    {
        $dates = [];

        foreach (['starts_at', 'ends_at'] as $field) {
            $value = $this->input($field);

            if (! is_string($value) || $value === '') {
                continue;
            }

            try {
                $dates[$field] = Carbon::parse($value)->utc()->toDateTimeString();
            } catch (Throwable) {
                // Leave unparseable values as they are so the "date" rule reports them.
            }
        }

        $this->merge($dates);
    }
}
