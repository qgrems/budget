<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\UserContext\Domain\Ports\Inbound\CommandInterface;
use App\UserContext\Domain\ValueObjects\UserId;

final readonly class ReplayAUserEventsCommand implements CommandInterface
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
