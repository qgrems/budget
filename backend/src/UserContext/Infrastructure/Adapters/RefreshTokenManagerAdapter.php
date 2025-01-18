<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Adapters;

use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use Doctrine\DBAL\Connection;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface as JWTRefreshTokenManagerInterface;

final readonly class RefreshTokenManagerAdapter implements RefreshTokenManagerInterface
{
    public function __construct(
        private JWTRefreshTokenManagerInterface $refreshTokenManager,
        private Connection $connection,
    ) {
    }

    #[\Override]
    public function get(string $refreshToken): ?RefreshTokenInterface
    {
        return $this->refreshTokenManager->get($refreshToken);
    }

    #[\Override]
    public function deleteAll(string $userEmail): void
    {
        $this->connection->createQueryBuilder()
            ->delete('refresh_tokens')
            ->where('username = :username')
            ->setParameter('username', $userEmail)
            ->executeStatement();
    }
}
