<?php

declare(strict_types=1);


namespace Karting\App\Provider;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /** @var array */
    protected $listen = [
        \Karting\Shared\ResourceCreated::class => [
            \Karting\Availability\Infrastructure\Listener\ResourceCreatedListener::class
        ],
        \Karting\Availability\Domain\ResourceReserved::class => [
            \Karting\App\ReadModel\Reservation\ResourceReservedListener::class,
        ],
        \Karting\Track\TrackCreated::class => [
            \Karting\App\ReadModel\Track\TrackCreatedListener::class
        ],
        \Karting\Kart\KartCreated::class => [
            \Karting\App\ReadModel\Kart\KartCreatedListener::class
        ],
        \Karting\Availability\Domain\StateChanged::class => [
            \Karting\App\ReadModel\Kart\StateChangedListener::class,
            \Karting\App\ReadModel\Track\StateChangedListener::class
        ],
    ];
}