<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\UpdateAUserLastnameCommand;
use App\UserManagement\Domain\Aggregates\User;

final readonly class UpdateAUserLastnameCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(UpdateAUserLastnameCommand $updateAUserLastnameCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $updateAUserLastnameCommand->getUserId());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->updateLastname(
            $updateAUserLastnameCommand->getUserLastname(),
            $updateAUserLastnameCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
