<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\UpdateAUserLastnameCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\ValueObjects\Lastname;
use App\UserManagement\Domain\ValueObjects\UserId;

final readonly class UpdateAUserLastnameCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(UpdateAUserLastnameCommand $updateAUserLastnameCommand): void
    {
        $events = $this->eventSourcedRepository->get($updateAUserLastnameCommand->getUuid());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->updateLastname(
            Lastname::create(
                $updateAUserLastnameCommand->getLastname(),
            ),
            UserId::create($updateAUserLastnameCommand->getUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
