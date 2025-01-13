<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_envelope_view')]
#[ORM\Index(name: 'idx_budget_envelope_view_user_uuid', columns: ['user_uuid'])]
#[ORM\Index(name: 'idx_budget_envelope_view_uuid', columns: ['uuid'])]
final class BudgetEnvelopeView implements BudgetEnvelopeViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    #[ORM\Column(name: 'current_amount', type: 'string', length: 13)]
    private(set) string $currentAmount;

    #[ORM\Column(name: 'targeted_amount', type: 'string', length: 13)]
    private(set) string $targetedAmount;

    #[ORM\Column(name: 'name', type: 'string', length: 50)]
    private(set) string $name;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private(set) string $userUuid;

    #[ORM\Column(name: 'is_deleted', type: 'boolean', options: ['default' => false])]
    private(set) bool $isDeleted;

    private function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeTargetedAmount $targetedAmount,
        BudgetEnvelopeName $name,
        BudgetEnvelopeUserId $userId,
        BudgetEnvelopeCurrentAmount $currentAmount,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        bool $isDeleted,
    ) {
        $this->uuid = (string) $budgetEnvelopeId;
        $this->currentAmount = (string) $currentAmount;
        $this->targetedAmount = (string) $targetedAmount;
        $this->name = (string) $name;
        $this->userUuid = (string) $userId;
        $this->isDeleted = $isDeleted;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    #[\Override]
    public static function fromRepository(array $budgetEnvelope): self
    {
        return new self(
            BudgetEnvelopeId::fromString($budgetEnvelope['uuid']),
            BudgetEnvelopeTargetedAmount::fromString(
                $budgetEnvelope['targeted_amount'],
                $budgetEnvelope['current_amount'],
            ),
            BudgetEnvelopeName::fromString($budgetEnvelope['name']),
            BudgetEnvelopeUserId::fromString($budgetEnvelope['user_uuid']),
            BudgetEnvelopeCurrentAmount::fromString(
                $budgetEnvelope['current_amount'],
                $budgetEnvelope['targeted_amount'],
            ),
            new \DateTimeImmutable($budgetEnvelope['created_at']),
            new \DateTime($budgetEnvelope['updated_at']),
            (bool) $budgetEnvelope['is_deleted'],
        )
        ;
    }

    public static function fromBudgetEnvelopeCreatedEvent(
        BudgetEnvelopeCreatedEvent $budgetEnvelopeCreatedEvent,
    ): self {
        return new self(
            BudgetEnvelopeId::fromString($budgetEnvelopeCreatedEvent->aggregateId),
            BudgetEnvelopeTargetedAmount::fromString(
                $budgetEnvelopeCreatedEvent->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeName::fromString($budgetEnvelopeCreatedEvent->name),
            BudgetEnvelopeUserId::fromString($budgetEnvelopeCreatedEvent->userId),
            BudgetEnvelopeCurrentAmount::fromString(
                '0.00',
                $budgetEnvelopeCreatedEvent->targetedAmount,
            ),
            $budgetEnvelopeCreatedEvent->occurredOn,
            \DateTime::createFromImmutable($budgetEnvelopeCreatedEvent->occurredOn),
            false,
        );
    }

    public static function fromEvents(\Generator $events): self
    {
        $budgetEnvelope = null;

        foreach ($events as $event) {
            if ($event['type'] !== BudgetEnvelopeCreatedEvent::class && $budgetEnvelope instanceof self) {
                $budgetEnvelope->apply($event['type']::fromArray(json_decode($event['payload'], true)));
                continue;
            }

            $budgetEnvelope = self::fromBudgetEnvelopeCreatedEvent($event['type']::fromArray(json_decode($event['payload'], true)));
        }

        return $budgetEnvelope;
    }

    public function fromEvent(EventInterface $event): void
    {
        $this->apply($event);
    }

    private function apply(EventInterface $event): void
    {
        match ($event::class) {
            BudgetEnvelopeCreatedEvent::class => $this->applyCreatedEvent($event),
            BudgetEnvelopeRenamedEvent::class => $this->applyRenamedEvent($event),
            BudgetEnvelopeCreditedEvent::class => $this->applyCreditedEvent($event),
            BudgetEnvelopeDebitedEvent::class => $this->applyDebitedEvent($event),
            BudgetEnvelopeDeletedEvent::class => $this->applyDeletedEvent($event),
            BudgetEnvelopeRewoundEvent::class => $this->applyRewoundEvent($event),
            BudgetEnvelopeReplayedEvent::class => $this->applyReplayedEvent($event),
            BudgetEnvelopeTargetedAmountUpdatedEvent::class => $this->applyTargetedAmountUpdatedEvent($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyCreatedEvent(BudgetEnvelopeCreatedEvent $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->userUuid = $event->userId;
        $this->name = $event->name;
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = '0.00';
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->isDeleted = false;
    }

    private function applyRenamedEvent(BudgetEnvelopeRenamedEvent $event): void
    {
        $this->name = $event->name;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyCreditedEvent(BudgetEnvelopeCreditedEvent $event): void
    {
        $this->currentAmount = (string) (floatval($this->currentAmount) + floatval($event->creditMoney));
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyDebitedEvent(BudgetEnvelopeDebitedEvent $event): void
    {
        $this->currentAmount = (string) (floatval($this->currentAmount) - floatval($event->debitMoney));
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyDeletedEvent(BudgetEnvelopeDeletedEvent $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyRewoundEvent(BudgetEnvelopeRewoundEvent $event): void
    {
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = $event->currentAmount;
        $this->name = $event->name;
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyReplayedEvent(BudgetEnvelopeReplayedEvent $event): void
    {
        $this->targetedAmount = $event->targetedAmount;
        $this->currentAmount = $event->currentAmount;
        $this->name = $event->name;
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyTargetedAmountUpdatedEvent(BudgetEnvelopeTargetedAmountUpdatedEvent $event): void
    {
        $this->targetedAmount = $event->targetedAmount;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'currentAmount' => $this->currentAmount,
            'targetedAmount' => $this->targetedAmount,
            'name' => $this->name,
        ];
    }
}
