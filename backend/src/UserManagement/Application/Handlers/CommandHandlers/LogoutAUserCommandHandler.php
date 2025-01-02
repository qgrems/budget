<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Handlers\CommandHandlers;

use App\UserManagement\Application\Commands\LogoutAUserCommand;
use App\UserManagement\Domain\Ports\Outbound\EntityManagerInterface;
use App\UserManagement\Domain\Ports\Outbound\RefreshTokenManagerInterface;

final readonly class LogoutAUserCommandHandler
{
    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(LogoutAUserCommand $logoutAUserCommand): void
    {
        $refreshToken = $this->refreshTokenManager->get($logoutAUserCommand->getRefreshToken());

        if (null === $refreshToken) {
            return;
        }

        $this->entityManager->remove($refreshToken);
        $this->entityManager->flush();
    }
}
