<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\CreateUserCommand;
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

final readonly class CreateUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UserViewRepositoryInterface     $userViewRepository,
        private PasswordHasherInterface         $userPasswordHasher,
    ) {
    }

    public function __invoke(CreateUserCommand $command): void
    {
        try {
            $this->eventSourcedRepository->get($command->getUuid());
        } catch (\RuntimeException $exception) {
            $aggregate = User::create(
                UserId::create($command->getUuid()),
                Email::create($command->getEmail()),
                Password::create($this->userPasswordHasher->hash(new UserView(), $command->getPassword())),
                Firstname::create($command->getFirstName()),
                Lastname::create($command->getLastName()),
                Consent::create($command->isConsentGiven()),
                $this->userViewRepository,
            );
            $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
            $aggregate->clearUncommitedEvent();

            return;
        }

        throw new UserAlreadyExistsException(UserAlreadyExistsException::MESSAGE, 400);
    }
}
