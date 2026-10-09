<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->repository = app(BookingRepositoryInterface::class);
});

it('binds the interface to the Eloquent repository', function () {
    expect($this->repository)->toBeInstanceOf(BookingRepository::class);
});

it('returns all bookings sorted by starts_at ascending', function () {
    $later = Booking::factory()->create(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHour()]);
    $soonest = Booking::factory()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);
    $middle = Booking::factory()->create(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);

    expect($this->repository->all()->pluck('id')->all())
        ->toBe([$soonest->id, $middle->id, $later->id]);
});

it('finds a booking by id', function () {
    $booking = Booking::factory()->create();

    expect($this->repository->findOrFail($booking->id)->is($booking))->toBeTrue();
});

it('throws when a booking does not exist', function () {
    $this->repository->findOrFail(999);
})->throws(ModelNotFoundException::class);

it('creates a booking', function () {
    $data = Booking::factory()->raw();

    $booking = $this->repository->create($data);

    expect($booking->exists)->toBeTrue();
    $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'customer_email' => $data['customer_email']]);
});

it('updates a booking', function () {
    $booking = Booking::factory()->create();

    $updated = $this->repository->update($booking, ['guests' => 7, 'status' => BookingStatus::Confirmed]);

    expect($updated->guests)->toBe(7)
        ->and($updated->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('deletes a booking', function () {
    $booking = Booking::factory()->create();

    $this->repository->delete($booking);

    $this->assertModelMissing($booking);
});
