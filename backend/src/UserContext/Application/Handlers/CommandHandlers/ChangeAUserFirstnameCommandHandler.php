<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserFirstnameCommand;
use App\UserContext\Domain\Aggregates\User;

final readonly class ChangeAUserFirstnameCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeAUserFirstnameCommand $command): void
    {
        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getUserId());
        $aggregate->updateFirstname($command->getFirstname(), $command->getUserId());
    }
}
