<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Commands;

use App\UserManagement\Domain\Ports\Inbound\CommandInterface;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserLastname;

final readonly class UpdateAUserLastnameCommand implements CommandInterface
{
    private string $userId;
    private string $userLastname;

    public function __construct(
        UserId $userId,
        UserLastname $userLastname,
    ) {
        $this->userId = (string) $userId;
        $this->userLastname = (string) $userLastname;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getUserLastname(): UserLastname
    {
        return UserLastname::fromString($this->userLastname);
    }
}
