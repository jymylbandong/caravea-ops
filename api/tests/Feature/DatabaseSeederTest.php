<?php

use App\Enums\BookingStatus;
use App\Models\Booking;

it('seeds demo bookings in every status', function () {
    $this->seed();

    expect(Booking::count())->toBe(10)
        ->and(Booking::where('status', BookingStatus::Pending)->count())->toBe(6)
        ->and(Booking::where('status', BookingStatus::Confirmed)->count())->toBe(3)
        ->and(Booking::where('status', BookingStatus::Cancelled)->count())->toBe(1);
});
