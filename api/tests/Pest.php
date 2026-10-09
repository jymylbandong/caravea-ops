<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

// Feature tests boot Laravel and run against a fresh in-memory SQLite database (see phpunit.xml).
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Unit tests don't boot Laravel. The Mockery integration makes ->once() etc. count as assertions
// and fail the test when an expected call never happens.
pest()->extend(PHPUnit\Framework\TestCase::class)
    ->use(MockeryPHPUnitIntegration::class)
    ->in('Unit');

/**
 * A valid create/update payload, as the frontend would send it (UTC ISO 8601 datetimes).
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validBookingPayload(array $overrides = []): array
{
    return array_merge([
        'customer_name' => 'Ada Lovelace',
        'customer_email' => 'ada@example.com',
        'resource' => 'Meeting Room A',
        'starts_at' => now()->addDay()->setTime(10, 0)->toIso8601ZuluString(),
        'ends_at' => now()->addDay()->setTime(11, 0)->toIso8601ZuluString(),
        'guests' => 4,
        'notes' => 'Projector needed',
    ], $overrides);
}
