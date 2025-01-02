<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\UserManagement\Application\Commands\UpdateAUserPasswordCommand;
use App\UserManagement\Domain\Aggregates\User;
use App\UserManagement\Domain\Exceptions\UserOldPasswordIsIncorrectException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\Password;
use App\UserManagement\Domain\ValueObjects\UserId;

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
        $events = $this->eventSourcedRepository->get($updateAUserPasswordCommand->getUuid());
        $aggregate = User::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $userView = $this->userViewRepository->findOneBy(['uuid' => $updateAUserPasswordCommand->getUuid()]);

        if (!$this->passwordHasher->verify($userView, $updateAUserPasswordCommand->getOldPassword())) {
            throw new UserOldPasswordIsIncorrectException(UserOldPasswordIsIncorrectException::MESSAGE, 400);
        }

        $aggregate->updatePassword(
            Password::create(
                $updateAUserPasswordCommand->getOldPassword(),
            ),
            Password::create(
                $this->passwordHasher->hash($userView, $updateAUserPasswordCommand->getNewPassword()),
            ),
            UserId::create($updateAUserPasswordCommand->getUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
