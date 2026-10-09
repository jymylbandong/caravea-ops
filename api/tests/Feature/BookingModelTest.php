<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;

it('casts status to the BookingStatus enum', function () {
    $booking = Booking::factory()->confirmed()->create();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('casts starts_at and ends_at to Carbon instances', function () {
    $booking = Booking::factory()->create()->fresh();

    expect($booking->starts_at)->toBeInstanceOf(Carbon::class)
        ->and($booking->ends_at)->toBeInstanceOf(Carbon::class)
        ->and($booking->ends_at->greaterThan($booking->starts_at))->toBeTrue();
});

it('stores datetimes in UTC', function () {
    $booking = Booking::factory()->create([
        'starts_at' => '2030-01-15 10:00:00',
        'ends_at' => '2030-01-15 11:00:00',
    ])->fresh();

    expect($booking->starts_at->timezoneName)->toBe('UTC')
        ->and($booking->starts_at->toDateTimeString())->toBe('2030-01-15 10:00:00');
});

it('allows notes to be null', function () {
    $booking = Booking::factory()->create(['notes' => null]);

    expect($booking->fresh()->notes)->toBeNull();
});
