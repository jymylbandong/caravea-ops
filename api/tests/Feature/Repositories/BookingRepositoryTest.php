<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\BookingRepository;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

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

describe('hasOverlap', function () {
    beforeEach(function () {
        // Existing booking: Meeting Room A, 10:00–11:00.
        $this->existing = Booking::factory()->create([
            'resource' => 'Meeting Room A',
            'starts_at' => '2030-01-15 10:00:00',
            'ends_at' => '2030-01-15 11:00:00',
        ]);
    });

    it('detects overlapping ranges', function (string $start, string $end) {
        expect($this->repository->hasOverlap('Meeting Room A', Carbon::parse($start), Carbon::parse($end)))->toBeTrue();
    })->with([
        'same range' => ['2030-01-15 10:00:00', '2030-01-15 11:00:00'],
        'starts during' => ['2030-01-15 10:30:00', '2030-01-15 11:30:00'],
        'ends during' => ['2030-01-15 09:30:00', '2030-01-15 10:30:00'],
        'inside' => ['2030-01-15 10:15:00', '2030-01-15 10:45:00'],
        'surrounds' => ['2030-01-15 09:00:00', '2030-01-15 12:00:00'],
    ]);

    it('allows back-to-back and separate ranges', function (string $start, string $end) {
        expect($this->repository->hasOverlap('Meeting Room A', Carbon::parse($start), Carbon::parse($end)))->toBeFalse();
    })->with([
        'ends when existing starts' => ['2030-01-15 09:00:00', '2030-01-15 10:00:00'],
        'starts when existing ends' => ['2030-01-15 11:00:00', '2030-01-15 12:00:00'],
        'different day' => ['2030-01-16 10:00:00', '2030-01-16 11:00:00'],
    ]);

    it('ignores other resources', function () {
        expect($this->repository->hasOverlap('Meeting Room B', Carbon::parse('2030-01-15 10:00:00'), Carbon::parse('2030-01-15 11:00:00')))->toBeFalse();
    });

    it('ignores cancelled bookings', function () {
        $this->existing->update(['status' => BookingStatus::Cancelled]);

        expect($this->repository->hasOverlap('Meeting Room A', Carbon::parse('2030-01-15 10:00:00'), Carbon::parse('2030-01-15 11:00:00')))->toBeFalse();
    });

    it('ignores the booking being updated', function () {
        expect($this->repository->hasOverlap(
            'Meeting Room A',
            Carbon::parse('2030-01-15 10:30:00'),
            Carbon::parse('2030-01-15 11:30:00'),
            $this->existing->id,
        ))->toBeFalse();
    });
});
