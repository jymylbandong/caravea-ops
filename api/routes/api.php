<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;

// {id} instead of {booking}: the controller resolves bookings through the service, not route model binding.
Route::apiResource('bookings', BookingController::class)
    ->only(['index', 'show', 'store'])
    ->parameters(['bookings' => 'id'])
    ->where(['id' => '[0-9]+']);
