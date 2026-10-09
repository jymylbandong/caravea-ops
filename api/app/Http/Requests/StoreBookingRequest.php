<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Http\Requests\Concerns\NormalizesBookingDates;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    use NormalizesBookingDates;

    /**
     * There is no auth in this module, so anyone may create a booking.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'string', 'email', 'max:255'],
            'resource' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'guests' => ['required', 'integer', 'between:1,20'],
            // Optional on create; BookingService defaults it to pending.
            'status' => ['sometimes', Rule::enum(BookingStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
