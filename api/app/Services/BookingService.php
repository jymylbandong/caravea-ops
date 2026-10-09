<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\BookingOverlapException;
use App\Models\Booking;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class BookingService
{
    public function __construct(
        private readonly BookingRepositoryInterface $bookings,
    ) {}

    public const DEFAULT_PER_PAGE = 10;

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function list(int $page = 1, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        return $this->bookings->paginate($perPage, $page);
    }

    public function find(int $id): Booking
    {
        return $this->bookings->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data  Validated input from StoreBookingRequest.
     *
     * @throws BookingOverlapException
     */
    public function create(array $data): Booking
    {
        // New bookings start as pending unless the client chose a status.
        $data['status'] ??= BookingStatus::Pending;

        $this->ensureNoOverlap($data);

        return $this->bookings->create($data);
    }

    /**
     * @param  array<string, mixed>  $data  Validated input from UpdateBookingRequest.
     *
     * @throws BookingOverlapException
     */
    public function update(int $id, array $data): Booking
    {
        $booking = $this->bookings->findOrFail($id);

        $this->ensureNoOverlap($data, ignoreId: $booking->id);

        return $this->bookings->update($booking, $data);
    }

    public function delete(int $id): void
    {
        $booking = $this->bookings->findOrFail($id);

        $this->bookings->delete($booking);
    }

    /**
     * A booking may not overlap another non-cancelled booking for the same resource.
     * Cancelled bookings never block a slot, so saving one as cancelled skips the check.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws BookingOverlapException
     */
    private function ensureNoOverlap(array $data, ?int $ignoreId = null): void
    {
        // Status arrives as a string from validated input, or as the enum default set in create().
        $status = $data['status'] instanceof BookingStatus ? $data['status'] : BookingStatus::from($data['status']);

        if ($status === BookingStatus::Cancelled) {
            return;
        }

        $overlaps = $this->bookings->hasOverlap(
            $data['resource'],
            Carbon::parse($data['starts_at']),
            Carbon::parse($data['ends_at']),
            $ignoreId,
        );

        if ($overlaps) {
            throw new BookingOverlapException;
        }
    }
}
