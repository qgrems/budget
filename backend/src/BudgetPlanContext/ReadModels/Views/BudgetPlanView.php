<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Views;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_plan_view')]
#[ORM\UniqueConstraint(name: 'unique_budget_plan_for_user', columns: ['user_uuid', 'date'])]
#[ORM\Index(name: 'idx_budget_plan_view_user_uuid', columns: ['user_uuid'])]
#[ORM\Index(name: 'idx_budget_plan_view_uuid', columns: ['uuid'])]
#[ORM\Index(name: 'idx_budget_plan_view_date', columns: ['date'])]
final class BudgetPlanView implements \JsonSerializable, BudgetPlanViewInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private(set) string $userId;

    #[ORM\Column(name: 'date', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $date;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private(set) bool $isDeleted;

    private function __construct(
        BudgetPlanId $budgetPlanId,
        BudgetPlanUserId $budgetPlanUserId,
        \DateTimeImmutable $date,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        bool $isDeleted,
    ) {
        $this->uuid = (string) $budgetPlanId;
        $this->userId = (string) $budgetPlanUserId;
        $this->date = $date;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->isDeleted = $isDeleted;
    }

    public static function fromBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanGeneratedDomainEvent->aggregateId),
            BudgetPlanUserId::fromString($budgetPlanGeneratedDomainEvent->userId),
            new \DateTimeImmutable($budgetPlanGeneratedDomainEvent->date),
            $budgetPlanGeneratedDomainEvent->occurredOn,
            \DateTime::createFromImmutable($budgetPlanGeneratedDomainEvent->occurredOn),
            false,
        );
    }

    public static function fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
    ): self {
        return new self(
            BudgetPlanId::fromString($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->aggregateId),
            BudgetPlanUserId::fromString($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->userId),
            new \DateTimeImmutable($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->date),
            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->occurredOn,
            \DateTime::createFromImmutable($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->occurredOn),
            false,
        );
    }

    public static function fromRepository(array $budgetPlan): BudgetPlanViewInterface
    {
        return new self(
            BudgetPlanId::fromString($budgetPlan['uuid']),
            BudgetPlanUserId::fromString($budgetPlan['user_uuid']),
            new \DateTimeImmutable($budgetPlan['date']),
            new \DateTimeImmutable($budgetPlan['created_at']),
            \DateTime::createFromImmutable(new \DateTimeImmutable($budgetPlan['updated_at'])),
            (bool) $budgetPlan['is_deleted'],
        );
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'userId' => $this->userId,
            'date' => $this->date->format(\DateTimeInterface::ATOM),
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'userId' => $this->userId,
            'date' => $this->date->format(\DateTimeInterface::ATOM),
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->applyBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanRemovedDomainEvent::class => $this->applyBudgetPlanRemovedDomainEvent($event),
            default => throw new \RuntimeException('Unknown event type'),
        };
    }

    private function applyBudgetPlanGeneratedDomainEvent(BudgetPlanGeneratedDomainEvent $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->userId = $event->userId;
        $this->date = new \DateTimeImmutable($event->date);
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->isDeleted = false;
    }

    private function applyBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $event,
    ): void {
        $this->uuid = $event->aggregateId;
        $this->userId = $event->userId;
        $this->date = new \DateTimeImmutable($event->date);
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->isDeleted = false;
    }

    private function applyBudgetPlanRemovedDomainEvent(
        BudgetPlanRemovedDomainEvent $budgetPlanRemovedDomainEvent,
    ): void {
        $this->isDeleted = $budgetPlanRemovedDomainEvent->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($budgetPlanRemovedDomainEvent->occurredOn);
    }
}
