<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\UpdateAUserFirstnameCommand;
use App\UserContext\Domain\Aggregates\User;

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
