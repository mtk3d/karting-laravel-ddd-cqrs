<?php

namespace Tests\Unit\Availability;

use App\Availability\Application\AvailabilityService;
use App\Availability\Domain\ResourceAvailabilityException;
use App\Availability\Domain\ResourceTurnedOn;
use App\Availability\Domain\ResourceWithdrawn;
use App\Availability\Infrastructure\Repository\InMemoryResourceRepository;
use App\Shared\Common\InMemoryDomainEventDispatcher;
use PHPUnit\Framework\TestCase;
use function Tests\Fixtures\aResource;
use function Tests\Fixtures\aWithdrawnResource;

class AvailabilityTest extends TestCase
{
    private InMemoryResourceRepository $resourceRepository;
    private InMemoryDomainEventDispatcher $eventDispatcher;
    private AvailabilityService $availabilityService;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourceRepository = new InMemoryResourceRepository();
        $this->eventDispatcher = new InMemoryDomainEventDispatcher();

        $this->availabilityService = new AvailabilityService($this->resourceRepository, $this->eventDispatcher);
    }

    public function testCreateResource(): void
    {
        $resource = aResource();
        $this->availabilityService->createResource($resource->getId());

        self::assertEquals($resource, $this->resourceRepository->find($resource->getId()));
    }

    public function testWithdrawResource(): void
    {
        // given
        $resource = aResource();
        $this->resourceRepository->save($resource);

        // when
        $this->availabilityService->withdrawResource($resource->getId());

        // then
        self::assertEquals(
            new ResourceWithdrawn($this->eventDispatcher->first()->eventId(), $resource->getId()),
            $this->eventDispatcher->first()
        );

        $resource = aWithdrawnResource($resource->getId());
        self::assertEquals($resource, $this->resourceRepository->find($resource->getId()));
    }

    public function testWithdrawWithdrawnResource(): void
    {
        // given
        $resource = aWithdrawnResource();
        $this->resourceRepository->save($resource);

        // should
        self::expectExceptionObject(new ResourceAvailabilityException('ResourceItem already withdrawn'));

        // when
        $this->availabilityService->withdrawResource($resource->getId());
    }

    public function testTurnOnWithdrawnResource(): void
    {
        // given
        $resource = aWithdrawnResource();
        $this->resourceRepository->save($resource);

        // when
        $this->availabilityService->turnOnResource($resource->getId());

        // then
        self::assertEquals(
            new ResourceTurnedOn($this->eventDispatcher->first()->eventId(), $resource->getId()),
            $this->eventDispatcher->first()
        );

        $resource = aResource($resource->getId());
        self::assertEquals($resource, $this->resourceRepository->find($resource->getId()));
    }

    public function testTurnOnResource(): void
    {
        // given
        $resource = aResource();
        $this->resourceRepository->save($resource);

        // should
        self::expectExceptionObject(new ResourceAvailabilityException('ResourceItem already turned on'));

        // when
        $this->availabilityService->turnOnResource($resource->getId());
    }
}
