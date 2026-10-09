<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('resource');
            // Stored in UTC (config/app.php timezone); the frontend converts to local time.
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedTinyInteger('guests');
            // Values come from App\Enums\BookingStatus. The default is applied in BookingService.
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamps();

            // The list is sorted by starts_at; the overlap check filters by resource and time range.
            $table->index('starts_at');
            $table->index(['resource', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
