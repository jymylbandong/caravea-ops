<?php

use App\Models\Booking;

beforeEach(function () {
    $this->freezeTime();

    // Existing booking: Meeting Room A, 10:00–11:00 UTC tomorrow.
    $this->start = now()->addDay()->setTime(10, 0);
    $this->existing = Booking::factory()->create([
        'resource' => 'Meeting Room A',
        'starts_at' => $this->start,
        'ends_at' => $this->start->copy()->addHour(),
    ]);
});

function rangePayload(string $resource, $start, $end, array $overrides = []): array
{
    return validBookingPayload([
        'resource' => $resource,
        'starts_at' => $start->toIso8601ZuluString(),
        'ends_at' => $end->toIso8601ZuluString(),
        ...$overrides,
    ]);
}

it('rejects an overlapping booking for the same resource', function () {
    $this->postJson('/api/bookings', rangePayload('Meeting Room A', $this->start->copy()->addMinutes(30), $this->start->copy()->addMinutes(90)))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['starts_at'])
        ->assertJsonPath('errors.starts_at.0', 'This resource is already booked for an overlapping time.');

    expect(Booking::count())->toBe(1);
});

it('detects overlaps across timezones', function () {
    // 18:30+08:00 is 10:30 UTC, inside the existing 10:00–11:00 UTC booking.
    $date = $this->start->format('Y-m-d');

    $this->postJson('/api/bookings', validBookingPayload([
        'resource' => 'Meeting Room A',
        'starts_at' => "{$date}T18:30:00+08:00",
        'ends_at' => "{$date}T19:30:00+08:00",
    ]))->assertUnprocessable()->assertOnlyJsonValidationErrors(['starts_at']);
});

it('allows a back-to-back booking', function () {
    $this->postJson('/api/bookings', rangePayload('Meeting Room A', $this->start->copy()->addHour(), $this->start->copy()->addHours(2)))
        ->assertCreated();
});

it('allows the same time for a different resource', function () {
    $this->postJson('/api/bookings', rangePayload('Meeting Room B', $this->start, $this->start->copy()->addHour()))
        ->assertCreated();
});

it('ignores cancelled bookings', function () {
    $this->existing->update(['status' => 'cancelled']);

    $this->postJson('/api/bookings', rangePayload('Meeting Room A', $this->start, $this->start->copy()->addHour()))
        ->assertCreated();
});

it('allows updating a booking without conflicting with itself', function () {
    $this->putJson("/api/bookings/{$this->existing->id}", rangePayload(
        'Meeting Room A',
        $this->start->copy()->addMinutes(30),
        $this->start->copy()->addMinutes(90),
        ['status' => 'confirmed'],
    ))->assertOk();
});

it('rejects an update that moves a booking onto another one', function () {
    $other = Booking::factory()->create([
        'resource' => 'Meeting Room A',
        'starts_at' => $this->start->copy()->addHours(3),
        'ends_at' => $this->start->copy()->addHours(4),
    ]);

    $this->putJson("/api/bookings/{$other->id}", rangePayload(
        'Meeting Room A',
        $this->start->copy()->addMinutes(30),
        $this->start->copy()->addMinutes(90),
        ['status' => 'pending'],
    ))->assertUnprocessable()->assertOnlyJsonValidationErrors(['starts_at']);

    expect($other->fresh()->starts_at->equalTo($this->start->copy()->addHours(3)))->toBeTrue();
});

it('allows cancelling an overlapping booking', function () {
    // An overlapping booking can exist if it was created while the other was cancelled.
    $overlapping = Booking::factory()->cancelled()->create([
        'resource' => 'Meeting Room A',
        'starts_at' => $this->start,
        'ends_at' => $this->start->copy()->addHour(),
    ]);

    $this->putJson("/api/bookings/{$overlapping->id}", rangePayload(
        'Meeting Room A',
        $this->start,
        $this->start->copy()->addHour(),
        ['status' => 'cancelled', 'notes' => 'Still cancelled'],
    ))->assertOk();
});
