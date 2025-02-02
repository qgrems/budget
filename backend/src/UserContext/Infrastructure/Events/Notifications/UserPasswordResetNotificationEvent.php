<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Events\Notifications;

use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;

final class UserPasswordResetNotificationEvent
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
        $this->type = 'UserPasswordReset';
    }

    public static function fromDomainEvent(UserPasswordResetDomainEvent $userPasswordResetDomainEvent): self
    {
        return new self(
            $userPasswordResetDomainEvent->aggregateId,
            $userPasswordResetDomainEvent->userId,
            $userPasswordResetDomainEvent->requestId,
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
