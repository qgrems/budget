<?php

declare(strict_types=1);

namespace App\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserContext\Application\Commands\ChangeAUserPasswordCommand;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\UserOldPasswordIsIncorrectException;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserPassword;

final readonly class ChangeAUserPasswordCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UserViewRepositoryInterface $userViewRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws UserOldPasswordIsIncorrectException
     */
    public function __invoke(ChangeAUserPasswordCommand $command): void
    {
        /** @var User $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getUserId());
        $userView = $this->userViewRepository->findOneBy(
            ['uuid' => (string) $command->getUserId()],
        );

        if (!$this->passwordHasher->verify($userView, (string) $command->getUserOldPassword())) {
            throw new UserOldPasswordIsIncorrectException();
        }

        $aggregate->updatePassword(
            $command->getUserOldPassword(),
            UserPassword::fromString(
                $this->passwordHasher->hash($userView, (string) $command->getUserNewPassword()),
            ),
            $command->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate);
    }
}
