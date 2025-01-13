<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\DeleteAUserCommand;
use App\UserContext\Domain\Aggregates\User;

final readonly class DeleteAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(DeleteAUserCommand $deleteAUserCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $deleteAUserCommand->getUserId(),
            ),
        );
        $aggregate->delete($deleteAUserCommand->getUserId());
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
