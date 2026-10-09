<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Thrown by BookingService when the resource is already booked for an overlapping time.
 */
class BookingOverlapException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This resource is already booked for an overlapping time.');
    }

    /**
     * Laravel calls render() automatically. Responds in the same shape as a Form Request
     * validation error, so the frontend shows the message next to the starts_at field.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'errors' => ['starts_at' => [$this->getMessage()]],
        ], 422);
    }
}
