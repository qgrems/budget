<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserLanguagePreferenceCommand;
use App\UserContext\Domain\Aggregates\User;

final readonly class ChangeAUserLanguagePreferenceCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeAUserLanguagePreferenceCommand $command): void
    {
        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getUserId());
        $aggregate->updateLanguagePreference($command->getLanguagePreference(), $command->getUserId());
    }
}
