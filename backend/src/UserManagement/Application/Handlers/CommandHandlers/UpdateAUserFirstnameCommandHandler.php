<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\UpdateAUserFirstnameCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\ValueObjects\Firstname;
use App\UserManagement\Domain\ValueObjects\UserId;

final readonly class UpdateAUserFirstnameCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(UpdateAUserFirstnameCommand $updateAUserFirstnameCommand): void
    {
        $events = $this->eventSourcedRepository->get($updateAUserFirstnameCommand->getUuid());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->updateFirstname(
            Firstname::create(
                $updateAUserFirstnameCommand->getFirstname(),
            ),
            UserId::create($updateAUserFirstnameCommand->getUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
