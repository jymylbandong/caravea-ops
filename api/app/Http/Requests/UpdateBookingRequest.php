<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * Full PUT: same rules as create, with two differences.
 */
class UpdateBookingRequest extends StoreBookingRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // No "after:now", so bookings that have started or passed can still be edited or cancelled.
            'starts_at' => ['required', 'date'],
            // The edit form always sends the current status.
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ]);
    }
}
