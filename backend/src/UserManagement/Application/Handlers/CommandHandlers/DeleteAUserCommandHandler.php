<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\DeleteAUserCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\ValueObjects\UserId;

final readonly class DeleteAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(DeleteAUserCommand $deleteAUserCommand): void
    {
        $events = $this->eventSourcedRepository->get($deleteAUserCommand->getUuid());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->delete(UserId::create($deleteAUserCommand->getUuid()));
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
