<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Commands;

use App\UserManagement\Domain\Ports\Inbound\CommandInterface;
use App\UserManagement\Domain\ValueObjects\UserId;

final readonly class DeleteAUserCommand implements CommandInterface
{
    private string $userId;

    public function __construct(
        UserId $userId,
    ) {
        $this->userId = (string) $userId;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }
}
