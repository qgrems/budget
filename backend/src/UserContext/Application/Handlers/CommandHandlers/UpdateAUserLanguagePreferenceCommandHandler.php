<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\UpdateAUserLanguagePreferenceCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;

final readonly class UpdateAUserLanguagePreferenceCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
    ) {
    }

    public function __invoke(UpdateAUserLanguagePreferenceCommand $updateAUserLanguagePreferenceCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $updateAUserLanguagePreferenceCommand->getUserId(),
            ),
            $this->eventEncryptor,
        );
        $aggregate->updateLanguagePreference(
            $updateAUserLanguagePreferenceCommand->getLanguagePreference(),
            $updateAUserLanguagePreferenceCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
