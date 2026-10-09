<?php

namespace App\Providers;

use App\Repositories\BookingRepository;
use App\Repositories\BookingRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Repository interfaces and the implementations the container should inject for them.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        BookingRepositoryInterface::class => BookingRepository::class,
    ];
}
