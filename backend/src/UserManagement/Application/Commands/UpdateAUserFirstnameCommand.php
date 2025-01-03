<?php

declare(strict_types=1);

namespace App\UserManagement\Application\Commands;

use App\UserManagement\Domain\Ports\Inbound\CommandInterface;
use App\UserManagement\Domain\ValueObjects\UserFirstname;
use App\UserManagement\Domain\ValueObjects\UserId;

final readonly class UpdateAUserFirstnameCommand implements CommandInterface
{
    private string $userId;
    private string $userFirstname;

    public function __construct(
        UserId $userId,
        UserFirstname $userFirstname,
    ) {
        $this->userId = (string) $userId;
        $this->userFirstname = (string) $userFirstname;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getFirstname(): UserFirstname
    {
        return UserFirstname::fromString($this->userFirstname);
    }
}
