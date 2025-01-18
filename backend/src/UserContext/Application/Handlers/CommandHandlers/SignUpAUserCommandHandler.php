<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\SignUpAUserCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\ReadModels\Views\UserView;

final readonly class SignUpAUserCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UserViewRepositoryInterface $userViewRepository,
        private PasswordHasherInterface $userPasswordHasher,
        private EventEncryptorInterface $eventEncryptor,
    ) {
    }

    public function __invoke(SignUpAUserCommand $signUpAUserCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $signUpAUserCommand->getUserId());

        if ($events->current()) {
            throw new UserAlreadyExistsException();
        }

        $aggregate = User::create(
            $signUpAUserCommand->getUserId(),
            $signUpAUserCommand->getUserEmail(),
            UserPassword::fromString(
                $this->userPasswordHasher->hash(
                    new UserView(
                        $signUpAUserCommand->getUserId(),
                        $signUpAUserCommand->getUserEmail(),
                        UserPassword::fromString((string) $signUpAUserCommand->getUserPassword()),
                        $signUpAUserCommand->getUserFirstname(),
                        $signUpAUserCommand->getUserLastname(),
                        $signUpAUserCommand->isUserConsentGiven(),
                        new \DateTimeImmutable(),
                        new \DateTimeImmutable(),
                        new \DateTime(),
                        ['ROLE_USER'],
                    ),
                    (string) $signUpAUserCommand->getUserPassword(),
                ),
            ),
            $signUpAUserCommand->getUserFirstname(),
            $signUpAUserCommand->getUserLastname(),
            $signUpAUserCommand->isUserConsentGiven(),
            $this->userViewRepository,
            $this->eventEncryptor,
        );

        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
