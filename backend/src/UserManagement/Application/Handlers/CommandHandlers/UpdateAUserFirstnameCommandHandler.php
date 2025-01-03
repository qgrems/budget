<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\UpdateAUserFirstnameCommand;
use App\UserManagement\Domain\Aggregates\User;

final readonly class UpdateAUserFirstnameCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(UpdateAUserFirstnameCommand $updateAUserFirstnameCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $updateAUserFirstnameCommand->getUserId());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->updateFirstname(
            $updateAUserFirstnameCommand->getFirstname(),
            $updateAUserFirstnameCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
