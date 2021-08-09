<?php

declare(strict_types=1);


namespace Karting\Reservation\Application;

use Karting\Availability\Application\Command\ReserveResource;
use Karting\Availability\Domain\ResourceReserved;
use Karting\Reservation\Application\Command\ConfirmReservation;
use Karting\Reservation\Domain\Kart;
use Karting\Reservation\Domain\ReservationCreated;
use Karting\Reservation\Domain\ReservationRepository;
use Karting\Shared\Common\CommandBus;

class ReservationManager
{
    private ReservationRepository $repository;
    private CommandBus $bus;

    public function __construct(ReservationRepository $repository, CommandBus $bus)
    {
        $this->repository = $repository;
        $this->bus = $bus;
    }

    public function handleReservationCrated(ReservationCreated $reservationCreated): void
    {
        $res = $this->repository->find($reservationCreated->reservationId());
        $res->karts()
            ->map(fn (Kart $kart): ReserveResource => new ReserveResource($kart->resourceId(), $res->period(), $res->id()))
            ->push(new ReserveResource($reservationCreated->track(), $reservationCreated->period(), $reservationCreated->reservationId()))
            ->each([$this->bus, 'dispatch']);
    }

    public function handleResourceReserved(ResourceReserved $resourceReserved): void
    {
        $reservation = $this->repository->find($resourceReserved->reservationId());

        $reservation->updateProgress($resourceReserved->resourceId());
        $this->repository->save($reservation);

        if ($reservation->finished() && !$reservation->confirmed()) {
            $this->bus->dispatch(new ConfirmReservation($reservation->id()));
        }
    }

    public function subscribe($events)
    {
        $events->listen(
            ReservationCreated::class,
            [ReservationManager::class, 'handleReservationCrated']
        );

        $events->listen(
            ResourceReserved::class,
            [ReservationManager::class, 'handleResourceReserved']
        );
    }
}
