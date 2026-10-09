<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return BookingResource::collection($this->bookings->list());
    }

    // Takes an id rather than route model binding, so the lookup goes through the repository.
    public function show(int $id): BookingResource
    {
        return new BookingResource($this->bookings->find($id));
    }

    public function store(StoreBookingRequest $request): BookingResource
    {
        // A freshly created model makes the resource respond with 201 Created.
        return new BookingResource($this->bookings->create($request->validated()));
    }

    public function update(UpdateBookingRequest $request, int $id): BookingResource
    {
        return new BookingResource($this->bookings->update($id, $request->validated()));
    }

    public function destroy(int $id): Response
    {
        $this->bookings->delete($id);

        return response()->noContent();
    }
}
