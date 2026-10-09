<?php

use App\Enums\BookingStatus;
use App\Exceptions\BookingOverlapException;
use App\Models\Booking;
use App\Repositories\BookingRepositoryInterface;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

// The service is tested against a mocked repository, so no database is involved.
beforeEach(function () {
    $this->repository = Mockery::mock(BookingRepositoryInterface::class);
    $this->service = new BookingService($this->repository);

    // Validated input as the Form Requests pass it on (datetimes already in UTC).
    $this->data = [
        'customer_name' => 'Ada',
        'resource' => 'Meeting Room A',
        'starts_at' => '2030-01-15 10:00:00',
        'ends_at' => '2030-01-15 11:00:00',
    ];
});

it('lists a page of bookings from the repository', function () {
    $page = new LengthAwarePaginator([new Booking, new Booking], total: 12, perPage: 5, currentPage: 2);
    $this->repository->shouldReceive('paginate')->once()->with(5, 2)->andReturn($page);

    expect($this->service->list(page: 2, perPage: 5))->toBe($page);
});

it('uses the default page size', function () {
    $this->repository->shouldReceive('paginate')
        ->once()
        ->with(BookingService::DEFAULT_PER_PAGE, 1)
        ->andReturn(new LengthAwarePaginator([], 0, BookingService::DEFAULT_PER_PAGE));

    $this->service->list();
});

it('finds a booking by id', function () {
    $booking = new Booking;
    $this->repository->shouldReceive('findOrFail')->once()->with(5)->andReturn($booking);

    expect($this->service->find(5))->toBe($booking);
});

it('defaults status to pending on create when none is given', function () {
    $this->repository->shouldReceive('hasOverlap')->andReturnFalse();
    $this->repository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(fn (array $data) => $data['status'] === BookingStatus::Pending
            && $data['customer_name'] === 'Ada'))
        ->andReturn(new Booking);

    $this->service->create($this->data);
});

it('keeps the status given on create', function () {
    $this->repository->shouldReceive('hasOverlap')->andReturnFalse();
    $this->repository->shouldReceive('create')
        ->once()
        ->with(Mockery::on(fn (array $data) => $data['status'] === 'confirmed'))
        ->andReturn(new Booking);

    $this->service->create([...$this->data, 'status' => 'confirmed']);
});

it('checks the resource and time range for overlaps on create', function () {
    $this->repository->shouldReceive('hasOverlap')
        ->once()
        ->with(
            'Meeting Room A',
            Mockery::on(fn (Carbon $start) => $start->toDateTimeString() === '2030-01-15 10:00:00'),
            Mockery::on(fn (Carbon $end) => $end->toDateTimeString() === '2030-01-15 11:00:00'),
            null,
        )
        ->andReturnFalse();
    $this->repository->shouldReceive('create')->once()->andReturn(new Booking);

    $this->service->create($this->data);
});

it('rejects an overlapping booking on create', function () {
    $this->repository->shouldReceive('hasOverlap')->once()->andReturnTrue();
    $this->repository->shouldNotReceive('create');

    $this->service->create($this->data);
})->throws(BookingOverlapException::class);

it('skips the overlap check when creating a cancelled booking', function () {
    $this->repository->shouldNotReceive('hasOverlap');
    $this->repository->shouldReceive('create')->once()->andReturn(new Booking);

    $this->service->create([...$this->data, 'status' => 'cancelled']);
});

it('updates an existing booking, ignoring itself in the overlap check', function () {
    $booking = (new Booking)->forceFill(['id' => 3]);
    $data = [...$this->data, 'status' => 'confirmed'];
    $this->repository->shouldReceive('findOrFail')->once()->with(3)->andReturn($booking);
    $this->repository->shouldReceive('hasOverlap')
        ->once()
        ->with('Meeting Room A', Mockery::type(Carbon::class), Mockery::type(Carbon::class), 3)
        ->andReturnFalse();
    $this->repository->shouldReceive('update')->once()->with($booking, $data)->andReturn($booking);

    expect($this->service->update(3, $data))->toBe($booking);
});

it('rejects an overlapping booking on update', function () {
    $booking = (new Booking)->forceFill(['id' => 3]);
    $this->repository->shouldReceive('findOrFail')->once()->with(3)->andReturn($booking);
    $this->repository->shouldReceive('hasOverlap')->once()->andReturnTrue();
    $this->repository->shouldNotReceive('update');

    $this->service->update(3, [...$this->data, 'status' => 'pending']);
})->throws(BookingOverlapException::class);

it('allows cancelling a booking without an overlap check', function () {
    $booking = (new Booking)->forceFill(['id' => 3]);
    $data = [...$this->data, 'status' => 'cancelled'];
    $this->repository->shouldReceive('findOrFail')->once()->with(3)->andReturn($booking);
    $this->repository->shouldNotReceive('hasOverlap');
    $this->repository->shouldReceive('update')->once()->with($booking, $data)->andReturn($booking);

    $this->service->update(3, $data);
});

it('deletes an existing booking', function () {
    $booking = new Booking;
    $this->repository->shouldReceive('findOrFail')->once()->with(3)->andReturn($booking);
    $this->repository->shouldReceive('delete')->once()->with($booking);

    $this->service->delete(3);
});

it('does not update or delete a missing booking', function (string $method, array $args) {
    $this->repository->shouldReceive('findOrFail')->once()->with(99)->andThrow(new ModelNotFoundException);
    $this->repository->shouldNotReceive('update', 'delete', 'hasOverlap');

    $this->service->{$method}(...$args);
})->with([
    'update' => ['update', [99, ['guests' => 2]]],
    'delete' => ['delete', [99]],
])->throws(ModelNotFoundException::class);
