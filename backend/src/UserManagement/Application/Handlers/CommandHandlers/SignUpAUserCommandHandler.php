<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\SignUpAUserCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\Exceptions\UserAlreadyExistsException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\Consent;
use App\UserManagement\Domain\ValueObjects\Email;
use App\UserManagement\Domain\ValueObjects\Firstname;
use App\UserManagement\Domain\ValueObjects\Lastname;
use App\UserManagement\Domain\ValueObjects\Password;
use App\UserManagement\Domain\ValueObjects\UserId;
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
            $this->eventSourcedRepository->get($signUpAUserCommand->getUuid());
        } catch (\RuntimeException $exception) {
            $aggregate = User::create(
                UserId::create($signUpAUserCommand->getUuid()),
                Email::create($signUpAUserCommand->getEmail()),
                Password::create($this->userPasswordHasher->hash(new UserView(), $signUpAUserCommand->getPassword())),
                Firstname::create($signUpAUserCommand->getFirstName()),
                Lastname::create($signUpAUserCommand->getLastName()),
                Consent::create($signUpAUserCommand->isConsentGiven()),
                $this->userViewRepository,
            );
            $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
            $aggregate->clearUncommitedEvent();

            return;
        }

        throw new UserAlreadyExistsException(UserAlreadyExistsException::MESSAGE, 400);
    }
}
