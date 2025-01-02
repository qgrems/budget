<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\ResetAUserPasswordCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\Exceptions\UserNotFoundException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Inbound\UserViewInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\Password;
use App\UserManagement\Domain\ValueObjects\UserId;

final readonly class ResetAUserPasswordCommandHandler
{
    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ResetAUserPasswordCommand $command): void
    {
        $userView = $this->userViewRepository->findOneBy(
            [
                'passwordResetToken' => $command->getResetToken(),
            ],
        );

        if (!$userView instanceof UserViewInterface) {
            throw new UserNotFoundException(UserNotFoundException::MESSAGE, 404);
        }

        $events = $this->eventSourcedRepository->get($userView->getUuid());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->resetPassword(
            Password::create(
                $this->passwordHasher->hash($userView, $command->getNewPassword()),
            ),
            UserId::create($userView->getUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
