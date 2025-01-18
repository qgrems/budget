<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\UpdateAUserFirstnameCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;

final readonly class UpdateAUserFirstnameCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
    ) {
    }

    public function __invoke(UpdateAUserFirstnameCommand $updateAUserFirstnameCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $updateAUserFirstnameCommand->getUserId(),
            ),
            $this->eventEncryptor,
        );
        $aggregate->updateFirstname(
            $updateAUserFirstnameCommand->getFirstname(),
            $updateAUserFirstnameCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
