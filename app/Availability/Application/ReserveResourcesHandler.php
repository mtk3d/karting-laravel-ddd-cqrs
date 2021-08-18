<?php

declare(strict_types=1);

namespace Karting\Availability\Application;

use Illuminate\Support\Collection;
use Karting\Availability\Application\Command\ReserveResources;
use Karting\Availability\Domain\ResourceItem;
use Karting\Availability\Domain\ResourceRepository;
use Karting\Availability\Domain\ResourceUnavailableException;
use Karting\Shared\Common\DomainEventBus;
use Karting\Shared\Common\Result;

class ReserveResourcesHandler
{
    public function __construct(
        private ResourceRepository $resourceRepository,
        private DomainEventBus $bus
    ) {
    }

    /**
     * @throws ResourceUnavailableException
     */
    public function handle(ReserveResources $reserveResources): void
    {
        /** @var Collection<int, ResourceItem> $resources */
        $resources = $this->resourceRepository->findAll($reserveResources->ids());

        $results = $resources->map(function (ResourceItem $resource) use ($reserveResources): Result {
            return $resource->reserve(
                $reserveResources->period(),
                $reserveResources->reservationId()
            );
        });

        $results->each(function (Result $result): void {
            if ($result->isFailure()) {
                throw new ResourceUnavailableException($result->reason());
            }
        });

        $this->resourceRepository->saveAll($resources);

        $results->map(fn (Result $result): Collection => $result->events())
            ->flatten()
            ->each([$this->bus, 'dispatch']);
    }
}