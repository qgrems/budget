<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\Anonymii\Ports\EventEncryptorInterface;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserFirstnameCommand;
use App\UserContext\Domain\Aggregates\User;

final readonly class ChangeAUserFirstnameCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(ChangeAUserFirstnameCommand $changeAUserFirstnameCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get((string) $changeAUserFirstnameCommand->getUserId()),
            $this->eventEncryptor,
            $this->eventClassMap,
        );
        $aggregate->updateFirstname(
            $changeAUserFirstnameCommand->getFirstname(),
            $changeAUserFirstnameCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
