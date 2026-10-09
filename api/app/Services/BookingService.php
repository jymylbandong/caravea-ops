<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
    ) {}

    /**
     * @return Collection<int, Booking>
     */
    public function list(): Collection
    {
        return $this->bookings->all();
    }

    public function find(int $id): Booking
    {
        return $this->bookings->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data  Validated input from StoreBookingRequest.
     */
    public function create(array $data): Booking
    {
        // New bookings start as pending unless the client chose a status.
        $data['status'] ??= BookingStatus::Pending;

        return $this->bookings->create($data);
    }

    /**
     * @param  array<string, mixed>  $data  Validated input from UpdateBookingRequest.
     */
    public function update(int $id, array $data): Booking
    {
        $booking = $this->bookings->findOrFail($id);

        return $this->bookings->update($booking, $data);
    }

    public function delete(int $id): void
    {
        $booking = $this->bookings->findOrFail($id);

        $this->bookings->delete($booking);
    }
}
