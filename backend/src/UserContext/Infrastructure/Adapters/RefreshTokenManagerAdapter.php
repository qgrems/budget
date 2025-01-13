<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface as JWTRefreshTokenManagerInterface;

final readonly class RefreshTokenManagerAdapter implements RefreshTokenManagerInterface
{
    public function __construct(private JWTRefreshTokenManagerInterface $refreshTokenManager)
    {
    }

    #[\Override]
    public function get(string $refreshToken): ?RefreshTokenInterface
    {
        return $this->refreshTokenManager->get($refreshToken);
    }
}
