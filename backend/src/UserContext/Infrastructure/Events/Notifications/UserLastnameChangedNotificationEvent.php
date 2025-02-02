<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Events\Notifications;

use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;

final class UserLastnameChangedNotificationEvent
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
        $this->type = 'UserLastnameChanged';
    }

    public static function fromDomainEvent(UserLastnameChangedDomainEvent $userLastnameChangedDomainEvent): self
    {
        return new self(
            $userLastnameChangedDomainEvent->aggregateId,
            $userLastnameChangedDomainEvent->userId,
            $userLastnameChangedDomainEvent->requestId,
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
