<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Gateway\User\Presentation\HTTP\DTOs\LogoutAUserInput;
use App\UserContext\Application\Commands\LogoutAUserCommand;
use App\UserContext\Application\Handlers\CommandHandlers\LogoutAUserCommandHandler;
use App\UserContext\Domain\Ports\Outbound\EntityManagerInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserContext\Infrastructure\Entities\RefreshToken;
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
        $command = new LogoutAUserCommand($logoutUserInput->refreshToken);

        $this->refreshTokenManager->method('get')->willReturn(new RefreshToken());
        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('flush');

        $this->handler->__invoke($command);
    }

    public function testLogoutUserWithWrongRefreshToken(): void
    {
        $logoutUserInput = new LogoutAUserInput('refreshToken');
        $command = new LogoutAUserCommand($logoutUserInput->refreshToken);

        $this->refreshTokenManager->method('get')->willReturn(null);
        $this->entityManager->expects($this->never())->method('remove');

        $this->handler->__invoke($command);
    }
}
