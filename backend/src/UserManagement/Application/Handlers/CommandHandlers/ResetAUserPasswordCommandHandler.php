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
use App\UserManagement\Domain\ValueObjects\UserPassword;
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
                'passwordResetToken' => (string) $command->getUserPasswordResetToken(),
            ],
        );

        if (!$userView instanceof UserViewInterface) {
            throw new UserNotFoundException(UserNotFoundException::MESSAGE, 404);
        }

        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                $userView->getUuid(),
            ),
        );
        $aggregate->resetPassword(
            UserPassword::fromString(
                $this->passwordHasher->hash($userView, (string) $command->getUserNewPassword()),
            ),
            UserId::fromString($userView->getUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
