<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Events\Notifications;

use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;

final class UserLanguagePreferenceChangedNotificationEvent
{
    public string $aggregateId;
    public string $userId;
    public string $requestId;
    public string $type;

    private function __construct(
        string $aggregateId,
        string $userId,
        string $requestId,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->requestId = $requestId;
        $this->type = 'UserLanguagePreferenceChanged';
    }

    public static function fromDomainEvent(
        UserLanguagePreferenceChangedDomainEvent $userLanguagePreferenceChangedDomainEvent
    ): self {
        return new self(
            $userLanguagePreferenceChangedDomainEvent->aggregateId,
            $userLanguagePreferenceChangedDomainEvent->userId,
            $userLanguagePreferenceChangedDomainEvent->requestId,
        );
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'requestId' => $this->requestId,
            'type' => $this->type,
        ];
    }
}
