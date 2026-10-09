<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

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
}
