<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\Anonymii\Services\EventEncryptorInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserLastnameCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Ports\Inbound\EventClassMapInterface;

final readonly class ChangeAUserLastnameCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(ChangeAUserLastnameCommand $changeAUserLastnameCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get((string) $changeAUserLastnameCommand->getUserId()),
            $this->eventEncryptor,
            $this->eventClassMap,
        );
        $aggregate->updateLastname(
            $changeAUserLastnameCommand->getUserLastname(),
            $changeAUserLastnameCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
