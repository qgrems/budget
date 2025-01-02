<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\RequestAUserPasswordResetCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\Exceptions\UserNotFoundException;
use App\UserManagement\Domain\Ports\Inbound\PasswordResetTokenGeneratorInterface;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\ValueObjects\PasswordResetToken;
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
        $userView = $this->userViewRepository->findOneBy(['email' => $requestAUserPasswordResetCommand->getEmail()]);

        if (!$userView) {
            throw new UserNotFoundException(UserNotFoundException::MESSAGE, 404);
        }

        $events = $this->eventSourcedRepository->get($userView->getUuid());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->setPasswordResetToken(
            PasswordResetToken::create($this->passwordResetTokenGenerator->generate()),
            UserId::create($userView->getUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
