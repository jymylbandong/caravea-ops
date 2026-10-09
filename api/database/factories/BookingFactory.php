<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Whole hours in the future, so bookings pass the "in the future" rule.
        $startsAt = now()->addDays(fake()->numberBetween(1, 30))->setTime(fake()->numberBetween(8, 17), 0);

        return [
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'resource' => fake()->randomElement(['Meeting Room A', 'Meeting Room B', 'Conference Hall']),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(fake()->numberBetween(1, 3)),
            'guests' => fake()->numberBetween(1, 20),
            'status' => BookingStatus::Pending,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Confirmed]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => BookingStatus::Cancelled]);
    }
}
