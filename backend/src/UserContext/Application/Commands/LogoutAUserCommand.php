<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\UserContext\Domain\Ports\Inbound\CommandInterface;

final readonly class LogoutAUserCommand implements CommandInterface
{
    public function __construct(
        private string $refreshToken,
    ) {
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }
}
