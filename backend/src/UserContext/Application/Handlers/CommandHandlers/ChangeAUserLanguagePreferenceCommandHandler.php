<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\Anonymii\Ports\EventEncryptorInterface;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserLanguagePreferenceCommand;
use App\UserContext\Domain\Aggregates\User;

final readonly class ChangeAUserLanguagePreferenceCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventEncryptorInterface $eventEncryptor,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(ChangeAUserLanguagePreferenceCommand $changeAUserLanguagePreferenceCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get((string) $changeAUserLanguagePreferenceCommand->getUserId()),
            $this->eventEncryptor,
            $this->eventClassMap,
        );
        $aggregate->updateLanguagePreference(
            $changeAUserLanguagePreferenceCommand->getLanguagePreference(),
            $changeAUserLanguagePreferenceCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
