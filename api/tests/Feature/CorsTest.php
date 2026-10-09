<?php

it('allows the frontend origin', function () {
    $this->withHeaders([
        'Origin' => 'http://localhost:3000',
        'Access-Control-Request-Method' => 'POST',
    ])->options('/api/bookings')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
});

it('does not allow other origins', function () {
    // With a single allowed origin, the CORS middleware always answers with that origin.
    // The browser blocks the request because it does not match the caller's origin.
    $response = $this->withHeaders([
        'Origin' => 'https://evil.example',
        'Access-Control-Request-Method' => 'POST',
    ])->options('/api/bookings');

    expect($response->headers->get('Access-Control-Allow-Origin'))
        ->not->toBe('https://evil.example')
        ->not->toBe('*');
});

it('reads the allowed origin from FRONTEND_URL', function () {
    expect(config('cors.allowed_origins'))->toBe([env('FRONTEND_URL', 'http://localhost:3000')]);
});
