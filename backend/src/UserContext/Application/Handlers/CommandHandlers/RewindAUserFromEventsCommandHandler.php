<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\RewindAUserFromEventsCommand;
use App\UserContext\Domain\Aggregates\User;

final readonly class RewindAUserFromEventsCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(RewindAUserFromEventsCommand $rewindAUserFromEventsCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $rewindAUserFromEventsCommand->getUserId(),
                $rewindAUserFromEventsCommand->getDesiredDateTime(),
            ),
        );
        $aggregate->rewind(
            $rewindAUserFromEventsCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
