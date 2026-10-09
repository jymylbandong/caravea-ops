<?php

use App\Models\Booking;

it('lists bookings sorted by starts_at ascending', function () {
    $later = Booking::factory()->create(['starts_at' => now()->addDays(3), 'ends_at' => now()->addDays(3)->addHour()]);
    $sooner = Booking::factory()->create(['starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour()]);

    $this->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $sooner->id)
        ->assertJsonPath('data.1.id', $later->id);
});

it('returns an empty list when there are no bookings', function () {
    $this->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.last_page', 1);
});

it('shows a single booking in the resource shape', function () {
    $booking = Booking::factory()->confirmed()->create([
        'starts_at' => '2030-01-15 10:00:00',
        'ends_at' => '2030-01-15 11:00:00',
        'notes' => null,
    ]);

    $this->getJson("/api/bookings/{$booking->id}")
        ->assertOk()
        ->assertJson(['data' => [
            'id' => $booking->id,
            'customer_name' => $booking->customer_name,
            'customer_email' => $booking->customer_email,
            'resource' => $booking->resource,
            'starts_at' => '2030-01-15T10:00:00Z',
            'ends_at' => '2030-01-15T11:00:00Z',
            'guests' => $booking->guests,
            'status' => 'confirmed',
            'notes' => null,
        ]])
        ->assertJsonStructure(['data' => ['created_at', 'updated_at']]);
});

it('returns 404 for a missing booking', function () {
    $this->getJson('/api/bookings/999')->assertNotFound();
});

it('returns a JSON 404 even without an Accept header', function () {
    $this->get('/api/bookings/999')
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertJsonStructure(['message']);
});

it('returns 404 for a non-numeric id', function () {
    $this->getJson('/api/bookings/abc')->assertNotFound();
});

describe('pagination', function () {
    beforeEach(function () {
        // 12 bookings on consecutive days, so page contents are predictable.
        $this->bookings = collect(range(1, 12))->map(fn (int $day) => Booking::factory()->create([
            'starts_at' => now()->addDays($day),
            'ends_at' => now()->addDays($day)->addHour(),
        ]));
    });

    it('returns the first page with 10 bookings by default', function () {
        $this->getJson('/api/bookings')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.id', $this->bookings[0]->id)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonStructure(['links' => ['first', 'last', 'prev', 'next']]);
    });

    it('returns the requested page and page size', function () {
        $this->getJson('/api/bookings?page=2&per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.id', $this->bookings[5]->id)
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('links.next', url('/api/bookings?per_page=5&page=3'));
    });

    it('returns an empty page past the end', function () {
        $this->getJson('/api/bookings?page=9')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 12);
    });

    it('rejects invalid pagination parameters', function (string $query, string $field) {
        $this->getJson("/api/bookings?{$query}")
            ->assertUnprocessable()
            ->assertOnlyJsonValidationErrors([$field]);
    })->with([
        'page 0' => ['page=0', 'page'],
        'page not a number' => ['page=abc', 'page'],
        'per_page 0' => ['per_page=0', 'per_page'],
        'per_page over 100' => ['per_page=101', 'per_page'],
    ]);
});
