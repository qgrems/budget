<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\UserManagement\Application\Commands\LogoutAUserCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\LogoutAUserCommandHandler;
use App\UserManagement\Domain\Ports\Outbound\EntityManagerInterface;
use App\UserManagement\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserManagement\Infrastructure\Entities\RefreshToken;
use App\UserManagement\Presentation\HTTP\DTOs\LogoutAUserInput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogoutAUserCommandHandlerTest extends TestCase
{
    private RefreshTokenManagerInterface&MockObject $refreshTokenManager;
    private EntityManagerInterface&MockObject $entityManager;
    private LogoutAUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new LogoutAUserCommandHandler(
            $this->refreshTokenManager,
            $this->entityManager,
        );
    }

    public function testLogoutUserSuccess(): void
    {
        $logoutUserInput = new LogoutAUserInput('refreshToken');
        $command = new LogoutAUserCommand($logoutUserInput->getRefreshToken());

        $this->refreshTokenManager->method('get')->willReturn(new RefreshToken());
        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $this->handler->__invoke($command);
    }

    public function testLogoutUserWithWrongRefreshToken(): void
    {
        $logoutUserInput = new LogoutAUserInput('refreshToken');
        $command = new LogoutAUserCommand($logoutUserInput->getRefreshToken());

        $this->refreshTokenManager->method('get')->willReturn(null);
        $this->entityManager->expects($this->never())->method('remove');

        $this->handler->__invoke($command);
    }
}
