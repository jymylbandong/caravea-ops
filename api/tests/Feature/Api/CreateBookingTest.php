<?php

use App\Models\Booking;

beforeEach(function () {
    $this->freezeTime();
});

it('creates a booking with valid data', function () {
    $payload = validBookingPayload();

    $response = $this->postJson('/api/bookings', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.customer_name', 'Ada Lovelace')
        ->assertJsonPath('data.customer_email', 'ada@example.com')
        ->assertJsonPath('data.resource', 'Meeting Room A')
        ->assertJsonPath('data.starts_at', $payload['starts_at'])
        ->assertJsonPath('data.ends_at', $payload['ends_at'])
        ->assertJsonPath('data.guests', 4)
        ->assertJsonPath('data.notes', 'Projector needed');

    $this->assertDatabaseHas('bookings', [
        'id' => $response->json('data.id'),
        'customer_email' => 'ada@example.com',
    ]);
});

it('defaults status to pending when none is given', function () {
    $this->postJson('/api/bookings', validBookingPayload())
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');
});

it('accepts an explicit status', function () {
    $this->postJson('/api/bookings', validBookingPayload(['status' => 'confirmed']))
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed');
});

it('allows notes to be omitted or null', function (array $notes) {
    $payload = validBookingPayload();
    unset($payload['notes']);
    $payload = array_merge($payload, $notes);

    $this->postJson('/api/bookings', $payload)
        ->assertCreated()
        ->assertJsonPath('data.notes', null);
})->with([
    'omitted' => [[]],
    'null' => [['notes' => null]],
]);

it('stores datetimes sent with an offset as UTC', function () {
    $date = now()->addDays(2)->format('Y-m-d');

    $response = $this->postJson('/api/bookings', validBookingPayload([
        'starts_at' => "{$date}T10:00:00+08:00",
        'ends_at' => "{$date}T11:30:00+08:00",
    ]));

    $response->assertCreated()
        ->assertJsonPath('data.starts_at', "{$date}T02:00:00Z")
        ->assertJsonPath('data.ends_at', "{$date}T03:30:00Z");
});

it('rejects ends_at before starts_at', function () {
    $this->postJson('/api/bookings', validBookingPayload([
        'starts_at' => now()->addDay()->setTime(11, 0)->toIso8601ZuluString(),
        'ends_at' => now()->addDay()->setTime(10, 0)->toIso8601ZuluString(),
    ]))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['ends_at']);
});

it('rejects ends_at equal to starts_at', function () {
    $startsAt = now()->addDay()->toIso8601ZuluString();

    $this->postJson('/api/bookings', validBookingPayload(['starts_at' => $startsAt, 'ends_at' => $startsAt]))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['ends_at']);
});

it('rejects starts_at in the past', function () {
    $this->postJson('/api/bookings', validBookingPayload([
        'starts_at' => now()->subHour()->toIso8601ZuluString(),
        'ends_at' => now()->addHour()->toIso8601ZuluString(),
    ]))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['starts_at']);
});

it('rejects an invalid email', function () {
    $this->postJson('/api/bookings', validBookingPayload(['customer_email' => 'not-an-email']))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['customer_email']);
});

it('rejects out-of-range guests', function (mixed $guests) {
    $this->postJson('/api/bookings', validBookingPayload(['guests' => $guests]))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['guests']);
})->with([0, 21, -1, 2.5, 'many']);

it('accepts guests at the boundaries', function (int $guests) {
    $this->postJson('/api/bookings', validBookingPayload(['guests' => $guests]))
        ->assertCreated()
        ->assertJsonPath('data.guests', $guests);
})->with([1, 20]);

it('rejects an unknown status', function () {
    $this->postJson('/api/bookings', validBookingPayload(['status' => 'archived']))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['status']);
});

it('rejects unparseable dates', function () {
    $this->postJson('/api/bookings', validBookingPayload(['starts_at' => 'not-a-date']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['starts_at']);
});

it('requires all mandatory fields', function () {
    $this->postJson('/api/bookings', [])
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors([
            'customer_name', 'customer_email', 'resource', 'starts_at', 'ends_at', 'guests',
        ]);

    expect(Booking::count())->toBe(0);
});

it('rejects a customer_name longer than 255 characters', function () {
    $this->postJson('/api/bookings', validBookingPayload(['customer_name' => str_repeat('a', 256)]))
        ->assertUnprocessable()
        ->assertOnlyJsonValidationErrors(['customer_name']);
});
