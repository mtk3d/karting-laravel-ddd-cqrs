<?php

declare(strict_types=1);


namespace Karting\Availability\Application;

use Karting\Availability\Application\Command\CreateResource;
use Karting\Availability\Domain\ResourceItem;
use Karting\Availability\Domain\ResourceRepository;
use Karting\Availability\Domain\StateChanged;
use Karting\Shared\Common\DomainEventBus;

class CreateResourceHandler
{
    private ResourceRepository $resourceRepository;
    private DomainEventBus $bus;

    public function __construct(ResourceRepository $resourceRepository, DomainEventBus $bus)
    {
        $this->resourceRepository = $resourceRepository;
        $this->bus = $bus;
    }

    public function handle(CreateResource $createResource): void
    {
        $resource = ResourceItem::of(
            $createResource->id(),
            $createResource->slots(),
            $createResource->enabled()
        );

        $this->resourceRepository->save($resource);

        $this->bus->dispatch(StateChanged::newOne($createResource->id(), $createResource->slots()->slots(), $createResource->enabled()));
    }
}
