<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ReplayAUserEventsCommand;
use App\UserContext\Domain\Aggregates\User;

final readonly class ReplayAUserEventsCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(ReplayAUserEventsCommand $replayAUserEventsCommand): void
    {
        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $replayAUserEventsCommand->getUserId());
        $aggregate->replay($replayAUserEventsCommand->getUserId());
    }
}
