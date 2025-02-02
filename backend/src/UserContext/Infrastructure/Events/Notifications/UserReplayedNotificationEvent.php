<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Events\Notifications;

use App\UserContext\Domain\Events\UserReplayedDomainEvent;

final class UserReplayedNotificationEvent
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
        $this->type = 'UserReplayed';
    }

    public static function fromDomainEvent(UserReplayedDomainEvent $userReplayedDomainEvent): self
    {
        return new self(
            $userReplayedDomainEvent->aggregateId,
            $userReplayedDomainEvent->userId,
            $userReplayedDomainEvent->requestId,
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
