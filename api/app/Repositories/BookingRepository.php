<?php

namespace App\Repositories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class BookingRepository implements BookingRepositoryInterface
{
    public function all(): Collection
    {
        // id breaks ties so bookings with the same start time keep a stable order.
        return Booking::query()
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();
    }

    public function findOrFail(int $id): Booking
    {
        return Booking::query()->findOrFail($id);
    }

    public function create(array $data): Booking
    {
        return Booking::query()->create($data);
    }

    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);

        return $booking;
    }

    public function delete(Booking $booking): void
    {
        $booking->delete();
    }

    public function hasOverlap(string $resource, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): bool
    {
        // Two ranges overlap when each one starts before the other ends.
        return Booking::query()
            ->where('resource', $resource)
            ->where('status', '!=', BookingStatus::Cancelled)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
