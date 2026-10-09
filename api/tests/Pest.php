<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

// Feature tests boot Laravel and run against a fresh in-memory SQLite database (see phpunit.xml).
pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Unit tests don't boot Laravel. The Mockery integration makes ->once() etc. count as assertions
// and fail the test when an expected call never happens.
pest()->extend(PHPUnit\Framework\TestCase::class)
    ->use(MockeryPHPUnitIntegration::class)
    ->in('Unit');
