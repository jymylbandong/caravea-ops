<?php

namespace App\Repositories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Collection;

interface BookingRepositoryInterface
{
    /**
     * All bookings, soonest first.
     *
     * @return Collection<int, Booking>
     */
    public function all(): Collection;

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
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
}
