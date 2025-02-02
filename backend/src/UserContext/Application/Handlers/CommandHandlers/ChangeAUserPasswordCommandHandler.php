<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserPasswordCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\UserOldPasswordIsIncorrectException;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserPassword;

final readonly class ChangeAUserPasswordCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UserViewRepositoryInterface $userViewRepository,
        private PasswordHasherInterface $passwordHasher,
        private EventEncryptorInterface $eventEncryptor,
    ) {
    }

    /**
     * @throws UserOldPasswordIsIncorrectException
     */
    public function __invoke(ChangeAUserPasswordCommand $changeAUserPasswordCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $changeAUserPasswordCommand->getUserId(),
            ),
            $this->eventEncryptor,
        );
        $userView = $this->userViewRepository->findOneBy(
            ['uuid' => (string) $changeAUserPasswordCommand->getUserId()],
        );

        if (!$this->passwordHasher->verify($userView, (string) $changeAUserPasswordCommand->getUserOldPassword())) {
            throw new UserOldPasswordIsIncorrectException();
        }

        $aggregate->updatePassword(
            $changeAUserPasswordCommand->getUserOldPassword(),
            UserPassword::fromString(
                $this->passwordHasher->hash($userView, (string) $changeAUserPasswordCommand->getUserNewPassword()),
            ),
            $changeAUserPasswordCommand->getUserId(),
            $this->eventEncryptor,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents());
        $aggregate->clearRaisedDomainEvents();
        $aggregate->clearKeys();
    }
}
