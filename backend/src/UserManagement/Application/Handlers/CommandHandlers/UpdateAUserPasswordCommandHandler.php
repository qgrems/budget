<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\UpdateAUserPasswordCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\Exceptions\UserOldPasswordIsIncorrectException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\UserPassword;

final readonly class UpdateAUserPasswordCommandHandler
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
    public function __invoke(UpdateAUserPasswordCommand $updateAUserPasswordCommand): void
    {
        $aggregate = User::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $updateAUserPasswordCommand->getUserId(),
            ),
        );
        $userView = $this->userViewRepository->findOneBy(
            ['uuid' => (string) $updateAUserPasswordCommand->getUserId()],
        );

        if (!$this->passwordHasher->verify($userView, (string) $updateAUserPasswordCommand->getUserOldPassword())) {
            throw new UserOldPasswordIsIncorrectException(UserOldPasswordIsIncorrectException::MESSAGE, 400);
        }

        $aggregate->updatePassword(
            $updateAUserPasswordCommand->getUserOldPassword(),
            UserPassword::fromString(
                $this->passwordHasher->hash($userView, (string) $updateAUserPasswordCommand->getUserNewPassword()),
            ),
            $updateAUserPasswordCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
