<?php

declare(strict_types=1);

namespace App\UserContext\Application\Commands;

use App\UserContext\Domain\Ports\Inbound\CommandInterface;
use App\UserContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Domain\ValueObjects\UserId;

final readonly class UpdateAUserLanguagePreferenceCommand implements CommandInterface
{
    private string $userId;
    private string $userLanguagePreference;

    public function __construct(
        UserId $userId,
        UserLanguagePreference $userLanguagePreference,
    ) {
        $this->userId = (string) $userId;
        $this->userLanguagePreference = (string) $userLanguagePreference;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getLanguagePreference(): UserLanguagePreference
    {
        return UserLanguagePreference::fromString($this->userLanguagePreference);
    }
}
