<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\RequestAUserPasswordResetCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\UserNotFoundException;
use App\UserContext\Domain\Ports\Inbound\PasswordResetTokenGeneratorInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;
use App\UserContext\Domain\ValueObjects\UserId;

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
            throw new UserNotFoundException();
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
