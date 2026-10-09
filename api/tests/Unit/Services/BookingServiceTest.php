<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Repositories\BookingRepositoryInterface;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// The service is tested against a mocked repository, so no database is involved.
beforeEach(function () {
    $this->repository = Mockery::mock(BookingRepositoryInterface::class);
    $this->service = new BookingService($this->repository);
});

it('lists bookings from the repository', function () {
    $bookings = new Collection([new Booking, new Booking]);
    $this->repository->shouldReceive('all')->once()->andReturn($bookings);

    expect($this->service->list())->toBe($bookings);
});

it('finds a booking by id', function () {
    $booking = new Booking;
    $this->repository->shouldReceive('findOrFail')->once()->with(5)->andReturn($booking);

    expect($this->service->find(5))->toBe($booking);
});

it('defaults status to pending on create when none is given', function () {
    $this->repository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(fn (array $data) => $data['status'] === BookingStatus::Pending
            && $data['customer_name'] === 'Ada'))
        ->andReturn(new Booking);

    $this->service->create(['customer_name' => 'Ada']);
});

it('keeps the status given on create', function () {
    $this->repository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(fn (array $data) => $data['status'] === 'confirmed'))
        ->andReturn(new Booking);

    $this->service->create(['customer_name' => 'Ada', 'status' => 'confirmed']);
});

it('updates an existing booking', function () {
    $booking = new Booking;
    $data = ['guests' => 4, 'status' => 'cancelled'];
    $this->repository->shouldReceive('findOrFail')->once()->with(3)->andReturn($booking);
    $this->repository->shouldReceive('update')->once()->with($booking, $data)->andReturn($booking);

    expect($this->service->update(3, $data))->toBe($booking);
});

it('deletes an existing booking', function () {
    $booking = new Booking;
    $this->repository->shouldReceive('findOrFail')->once()->with(3)->andReturn($booking);
    $this->repository->shouldReceive('delete')->once()->with($booking);

    $this->service->delete(3);
});

it('does not update or delete a missing booking', function (string $method, array $args) {
    $this->repository->shouldReceive('findOrFail')->once()->with(99)->andThrow(new ModelNotFoundException);
    $this->repository->shouldNotReceive('update', 'delete');

    $this->service->{$method}(...$args);
})->with([
    'update' => ['update', [99, ['guests' => 2]]],
    'delete' => ['delete', [99]],
])->throws(ModelNotFoundException::class);
