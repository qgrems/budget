<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ReplayAUserEventsCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;

final readonly class ReplayAUserEventsCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(ReplayAUserEventsCommand $replayAUserEventsCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get((string) $replayAUserEventsCommand->getUserId()),
            $this->eventEncryptor,
            $this->eventClassMap,
        );
        $aggregate->replay(
            $replayAUserEventsCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
