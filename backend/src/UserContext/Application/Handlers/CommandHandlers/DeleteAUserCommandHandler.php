<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\DeleteAUserCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Builders\UserEmailRegistryBuilder;

final readonly class DeleteAUserCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(DeleteAUserCommand $deleteAUserCommand): void
    {
        /** @var User $user */
        $user = $this->eventSourcedRepository->get((string) $deleteAUserCommand->getUserId());
        $registryBuilder = UserEmailRegistryBuilder::build($this->eventSourcedRepository)
            ->loadOrCreateRegistry()
            ->releaseEmail(
                $user->getEmail(),
                $deleteAUserCommand->getUserId()
            );
        $user->delete($deleteAUserCommand->getUserId());
        $aggregatesToSave = [];

        if ($registryAggregate = $registryBuilder->getRegistryAggregate()) {
            $aggregatesToSave[] = $registryAggregate;
        }

        $aggregatesToSave[] = $user;
        $this->eventSourcedRepository->saveMultiAggregate($aggregatesToSave);
    }
}
