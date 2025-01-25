<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_store', uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'unique_aggregate_version', columns: ['aggregate_id', 'type', 'version'])
])]
#[ORM\Index(name: 'idx_aggregate_id', columns: ['aggregate_id'])]
#[ORM\Index(name: 'idx_type', columns: ['type'])]
#[ORM\Index(name: 'idx_user_id', columns: ['user_id'])]
#[ORM\Index(name: 'idx_occurred_on', columns: ['occurred_on'])]
final class EventStore
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id {
        get {
            return $this->id;
        }
        set {
            $this->id = $value;
        }
    }

    #[ORM\Column(name: 'aggregate_id', type: 'string', length: 36)]
    private string $aggregateId {
        get {
            return $this->aggregateId;
        }
        set {
            $this->aggregateId = $value;
        }
    }

    #[ORM\Column(name: 'user_id', type: 'string', length: 36)]
    private string $userId {
        get {
            return $this->userId;
        }
        set {
            $this->userId = $value;
        }
    }

    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    private string $type {
        get {
            return $this->type;
        }
        set {
            $this->type = $value;
        }
    }

    #[ORM\Column(name: 'version', type: 'integer', options: ['default' => 0])]
    public int $version = 0 {
        get {
            return $this->version;
        }
        set {
            $this->version = $value;
        }
    }

    #[ORM\Column(name: 'request_id', type: 'string', length: 36)]
    public string $requestId {
        get {
            return $this->requestId;
        }
        set {
            $this->requestId = $value;
        }
    }

    #[ORM\Column(name: 'payload', type: 'json')]
    private array $payload {
        get {
            return $this->payload;
        }
        set {
            $this->payload = $value;
        }
    }

    #[ORM\Column(name:'occurred_on', type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredOn {
        get {
            return $this->occurredOn;
        }
        set {
            $this->occurredOn = $value;
        }
    }
}
