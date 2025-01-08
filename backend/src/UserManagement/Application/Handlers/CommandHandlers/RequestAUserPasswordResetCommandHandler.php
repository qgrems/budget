<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\RequestAUserPasswordResetCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\Exceptions\UserNotFoundException;
use App\UserManagement\Domain\Ports\Inbound\PasswordResetTokenGeneratorInterface;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\ValueObjects\UserPasswordResetToken;
use App\UserManagement\Domain\ValueObjects\UserId;

final readonly class RequestAUserPasswordResetCommandHandler
{
    public function __construct(
        private UserViewRepositoryInterface $userViewRepository,
        private PasswordResetTokenGeneratorInterface $passwordResetTokenGenerator,
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(RequestAUserPasswordResetCommand $requestAUserPasswordResetCommand): void
    {
        $userView = $this->userViewRepository->findOneBy(['email' => (string) $requestAUserPasswordResetCommand->getUserEmail()]);

        if (!$userView) {
            throw new UserNotFoundException(UserNotFoundException::MESSAGE, 404);
        }

        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                $userView->getUuid(),
            ),
        );
        $aggregate->setPasswordResetToken(
            UserPasswordResetToken::fromString($this->passwordResetTokenGenerator->generate()),
            UserId::fromString($userView->getUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
