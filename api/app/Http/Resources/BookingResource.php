<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Booking $resource
 */
class BookingResource extends JsonResource
{
    /**
     * Datetimes are sent as UTC ISO 8601 strings ("2030-01-15T10:00:00Z") for the frontend to localise.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Read from the model explicitly: JsonResource's own $resource property is the wrapped
        // model, so $this->resource would shadow the booking's "resource" column.
        $booking = $this->resource;

        return [
            'id' => $booking->id,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'resource' => $booking->resource,
            'starts_at' => $booking->starts_at->toIso8601ZuluString(),
            'ends_at' => $booking->ends_at->toIso8601ZuluString(),
            'guests' => $booking->guests,
            'status' => $booking->status->value,
            'notes' => $booking->notes,
            'created_at' => $booking->created_at?->toIso8601ZuluString(),
            'updated_at' => $booking->updated_at?->toIso8601ZuluString(),
        ];
    }
}
