<?php

namespace App\SharedContext\Infrastructure\Entities;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'event_store')]
#[ORM\Index(name: 'idx_aggregate_id', columns: ['aggregate_id'])]
class EventStore
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

    #[ORM\Column(name: 'type', type: 'string', length: 255)]
    private string $type {
        get {
            return $this->type;
        }
        set {
            $this->type = $value;
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
