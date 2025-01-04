<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\DeleteAUserCommand;
use App\UserManagement\Domain\Aggregates\User;

final readonly class DeleteAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(DeleteAUserCommand $deleteAUserCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $deleteAUserCommand->getUserId());
        $aggregate = User::fromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->delete($deleteAUserCommand->getUserId());
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
