<?php

use App\Enums\BookingStatus;
use App\Models\Booking;

beforeEach(function () {
    $this->freezeTime();
    $this->booking = Booking::factory()->create();
});

it('updates a booking', function () {
    $payload = validBookingPayload([
        'customer_name' => 'Grace Hopper',
        'guests' => 12,
        'status' => 'confirmed',
        'notes' => 'Moved to the afternoon',
    ]);

    $this->putJson("/api/bookings/{$this->booking->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.id', $this->booking->id)
        ->assertJsonPath('data.customer_name', 'Grace Hopper')
        ->assertJsonPath('data.guests', 12)
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.starts_at', $payload['starts_at'])
        ->assertJsonPath('data.notes', 'Moved to the afternoon');

    $fresh = $this->booking->fresh();
    expect($fresh->customer_name)->toBe('Grace Hopper')
        ->and($fresh->status)->toBe(BookingStatus::Confirmed);
});

it('cancels a booking', function () {
    $this->putJson("/api/bookings/{$this->booking->id}", validBookingPayload(['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('allows updating a booking whose start time has passed', function () {
    $past = Booking::factory()->create([
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->subDays(2)->addHour(),
    ]);

    $this->putJson("/api/bookings/{$past->id}", validBookingPayload([
        'starts_at' => $past->starts_at->toIso8601ZuluString(),
        'ends_at' => $past->ends_at->toIso8601ZuluString(),
        'status' => 'cancelled',
    ]))
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('clears notes when null is sent', function () {
    $this->booking->update(['notes' => 'Old note']);

    $this->putJson("/api/bookings/{$this->booking->id}", validBookingPayload(['status' => 'pending', 'notes' => null]))
        ->assertOk()
        ->assertJsonPath('data.notes', null);
});

it('stores datetimes sent with an offset as UTC', function () {
    $this->putJson("/api/bookings/{$this->booking->id}", validBookingPayload([
        'starts_at' => '2030-01-15T10:00:00+08:00',
        'ends_at' => '2030-01-15T11:00:00+08:00',
        'status' => 'pending',
    ]))
        ->assertOk()
        ->assertJsonPath('data.starts_at', '2030-01-15T02:00:00Z');
});

it('requires status on update', function () {
    $this->putJson("/api/bookings/{$this->booking->id}", validBookingPayload())
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['status']);
});

it('rejects a partial payload', function () {
    $this->putJson("/api/bookings/{$this->booking->id}", ['status' => 'confirmed'])
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors([
            'customer_name', 'customer_email', 'resource', 'starts_at', 'ends_at', 'guests',
        ]);
});

it('rejects invalid data on update and leaves the booking unchanged', function (array $overrides, string $field) {
    $before = $this->booking->fresh()->toArray();

    $this->putJson("/api/bookings/{$this->booking->id}", validBookingPayload(['status' => 'pending', ...$overrides]))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors([$field]);

    expect($this->booking->fresh()->toArray())->toBe($before);
})->with([
    'ends_at before starts_at' => [[
        'starts_at' => '2030-01-15T11:00:00Z',
        'ends_at' => '2030-01-15T10:00:00Z',
    ], 'ends_at'],
    'invalid email' => [['customer_email' => 'nope'], 'customer_email'],
    'too many guests' => [['guests' => 21], 'guests'],
    'unknown status' => [['status' => 'archived'], 'status'],
]);

it('returns 404 when updating a missing booking', function () {
    $this->putJson('/api/bookings/999', validBookingPayload(['status' => 'pending']))
        ->assertNotFound();
});
