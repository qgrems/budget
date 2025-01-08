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
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $updateAUserFirstnameCommand->getUserId(),
            ),
        );
        $aggregate->updateFirstname(
            $updateAUserFirstnameCommand->getFirstname(),
            $updateAUserFirstnameCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
