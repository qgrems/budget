<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserLanguagePreferenceCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;

final readonly class ChangeAUserLanguagePreferenceCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
    ) {
    }

    public function __invoke(ChangeAUserLanguagePreferenceCommand $changeAUserLanguagePreferenceCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $changeAUserLanguagePreferenceCommand->getUserId(),
            ),
            $this->eventEncryptor,
        );
        $aggregate->updateLanguagePreference(
            $changeAUserLanguagePreferenceCommand->getLanguagePreference(),
            $changeAUserLanguagePreferenceCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
