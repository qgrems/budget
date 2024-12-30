<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\UserManagement\Application\Commands\LogoutUserCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\LogoutUserCommandHandler;
use App\UserManagement\Domain\Ports\Outbound\EntityManagerInterface;
use App\UserManagement\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserManagement\Infrastructure\Entities\RefreshToken;
use App\UserManagement\Presentation\HTTP\DTOs\LogoutUserInput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogoutUserCommandHandlerTest extends TestCase
{
    private RefreshTokenManagerInterface&MockObject $refreshTokenManager;
    private EntityManagerInterface&MockObject $entityManager;
    private LogoutUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new LogoutUserCommandHandler(
            $this->refreshTokenManager,
            $this->entityManager,
        );
    }

    public function testLogoutUserSuccess(): void
    {
        $logoutUserInput = new LogoutUserInput('refreshToken');
        $command = new LogoutUserCommand($logoutUserInput->getRefreshToken());

        $this->refreshTokenManager->method('get')->willReturn(New RefreshToken());
        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $this->handler->__invoke($command);
    }

    public function testLogoutUserWithWrongRefreshToken(): void
    {
        $logoutUserInput = new LogoutUserInput('refreshToken');
        $command = new LogoutUserCommand($logoutUserInput->getRefreshToken());

        $this->refreshTokenManager->method('get')->willReturn(null);
        $this->entityManager->expects($this->never())->method('remove');

        $this->handler->__invoke($command);
    }
}
