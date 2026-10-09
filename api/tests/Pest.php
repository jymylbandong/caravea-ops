<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

// Feature tests boot Laravel and run against a fresh in-memory SQLite database (see phpunit.xml).
pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
