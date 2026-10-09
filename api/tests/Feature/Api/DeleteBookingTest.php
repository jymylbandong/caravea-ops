<?php

use App\Models\Booking;

it('deletes a booking', function () {
    $booking = Booking::factory()->create();

    $this->deleteJson("/api/bookings/{$booking->id}")
        ->assertNoContent();

    $this->assertModelMissing($booking);
    $this->getJson("/api/bookings/{$booking->id}")->assertNotFound();
});

it('only deletes the requested booking', function () {
    [$keep, $delete] = Booking::factory()->count(2)->create();

    $this->deleteJson("/api/bookings/{$delete->id}")->assertNoContent();

    $this->assertModelExists($keep);
});

it('returns 404 when deleting a missing booking', function () {
    $this->deleteJson('/api/bookings/999')->assertNotFound();
});
