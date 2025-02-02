<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Events\Notifications;

use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;

final class UserFirstnameChangedNotificationEvent
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
        $this->type = 'UserFirstnameChanged';
    }

    public static function fromDomainEvent(UserFirstnameChangedDomainEvent $userFirstnameChangedDomainEvent): self
    {
        return new self(
            $userFirstnameChangedDomainEvent->aggregateId,
            $userFirstnameChangedDomainEvent->userId,
            $userFirstnameChangedDomainEvent->requestId,
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
