<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Outbound;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;

interface RefreshTokenManagerInterface
{
    public function get(string $refreshToken): ?RefreshTokenInterface;

    public function deleteAll(string $userEmail): void;
}
