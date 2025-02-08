<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\DeleteAUserCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;

final readonly class DeleteAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(DeleteAUserCommand $deleteAUserCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get((string) $deleteAUserCommand->getUserId()),
            $this->eventEncryptor,
            $this->eventClassMap,
        );
        $aggregate->delete($deleteAUserCommand->getUserId());
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
