<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

interface BookingRepositoryInterface
{
    /**
     * One page of bookings, soonest first.
     *
     * @return LengthAwarePaginator<int, Booking>
     */
    public function paginate(int $perPage, int $page): LengthAwarePaginator;

    /**
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id): Booking;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Booking;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Booking $booking, array $data): Booking;

    public function delete(Booking $booking): void;

    /**
     * Whether a non-cancelled booking for the resource overlaps the given time range.
     * Back-to-back bookings (one ends exactly when the other starts) do not overlap.
     *
     * @param  int|null  $ignoreId  The booking being updated, so it is not compared with itself.
     */
    public function hasOverlap(string $resource, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): bool;
}
