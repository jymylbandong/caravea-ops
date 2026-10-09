<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route;

// {id} instead of {booking}: the controller resolves bookings through the service, not route model binding.
Route::apiResource('bookings', BookingController::class)
    ->parameters(['bookings' => 'id'])
    ->where(['id' => '[0-9]+']);
