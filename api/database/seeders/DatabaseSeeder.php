<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed demo bookings in every status.
     */
    public function run(): void
    {
        Booking::factory()->count(6)->create();
        Booking::factory()->confirmed()->count(3)->create();
        Booking::factory()->cancelled()->create();
    }
}
