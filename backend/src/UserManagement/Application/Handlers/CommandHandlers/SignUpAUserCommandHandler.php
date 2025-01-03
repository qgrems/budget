<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\SignUpAUserCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\Exceptions\UserAlreadyExistsException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\UserPassword;
use App\UserManagement\ReadModels\Views\UserView;

final readonly class SignUpAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UserViewRepositoryInterface $userViewRepository,
        private PasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function __invoke(SignUpAUserCommand $signUpAUserCommand): void
    {
        try {
            $this->eventSourcedRepository->get((string) $signUpAUserCommand->getUserId());
        } catch (\RuntimeException $exception) {
            $aggregate = User::create(
                $signUpAUserCommand->getUserId(),
                $signUpAUserCommand->getUserEmail(),
                UserPassword::fromString($this->userPasswordHasher->hash(new UserView(), (string) $signUpAUserCommand->getUserPassword())),
                $signUpAUserCommand->getUserFirstname(),
                $signUpAUserCommand->getUserLastname(),
                $signUpAUserCommand->isUserConsentGiven(),
                $this->userViewRepository,
            );
            $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
            $aggregate->clearUncommitedEvent();

            return;
        }

        throw new UserAlreadyExistsException(UserAlreadyExistsException::MESSAGE, 400);
    }
}
