<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
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

    #[ORM\Column(name: 'currency', type: 'string', length: 3)]
    private(set) string $currency;

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
        BudgetEnvelopeCurrency $currency,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        bool $isDeleted,
    ) {
        $this->uuid = (string) $budgetEnvelopeId;
        $this->currentAmount = (string) $currentAmount;
        $this->targetedAmount = (string) $targetedAmount;
        $this->name = (string) $name;
        $this->userUuid = (string) $userId;
        $this->currency = (string) $currency;
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
            BudgetEnvelopeCurrency::fromString($budgetEnvelope['currency']),
            new \DateTimeImmutable($budgetEnvelope['created_at']),
            new \DateTime($budgetEnvelope['updated_at']),
            (bool) $budgetEnvelope['is_deleted'],
        )
        ;
    }

    public static function fromBudgetEnvelopeAddedDomainEvent(
        BudgetEnvelopeAddedDomainEvent $budgetEnvelopeAddedDomainEvent,
    ): self {
        return new self(
            BudgetEnvelopeId::fromString($budgetEnvelopeAddedDomainEvent->aggregateId),
            BudgetEnvelopeTargetedAmount::fromString(
                $budgetEnvelopeAddedDomainEvent->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeName::fromString($budgetEnvelopeAddedDomainEvent->name),
            BudgetEnvelopeUserId::fromString($budgetEnvelopeAddedDomainEvent->userId),
            BudgetEnvelopeCurrentAmount::fromString(
                '0.00',
                $budgetEnvelopeAddedDomainEvent->targetedAmount,
            ),
            BudgetEnvelopeCurrency::fromString($budgetEnvelopeAddedDomainEvent->currency),
            $budgetEnvelopeAddedDomainEvent->occurredOn,
            \DateTime::createFromImmutable($budgetEnvelopeAddedDomainEvent->occurredOn),
            false,
        );
    }

    public static function fromEvents(\Generator $events): self
    {
        $budgetEnvelope = null;

        /** @var array{type: string, payload: string} $event */
        foreach ($events as $event) {
            if ($event['type'] !== BudgetEnvelopeAddedDomainEvent::class && $budgetEnvelope instanceof self) {
                $budgetEnvelope->apply($event['type']::fromArray(json_decode($event['payload'], true)));
                continue;
            }

            $budgetEnvelope = self::fromBudgetEnvelopeAddedDomainEvent(
                $event['type']::fromArray(json_decode($event['payload'], true)),
            );
        }

        return $budgetEnvelope;
    }

    public function fromEvent(DomainEventInterface $event): void
    {
        $this->apply($event);
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetEnvelopeAddedDomainEvent::class => $this->applyBudgetEnvelopeAddedDomainEvent($event),
            BudgetEnvelopeRenamedDomainEvent::class => $this->applyBudgetEnvelopeRenamedDomainEvent($event),
            BudgetEnvelopeCreditedDomainEvent::class => $this->applyBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->applyBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeDeletedDomainEvent::class => $this->applyBudgetEnvelopeDeletedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->applyBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->applyBudgetEnvelopeReplayedDomainEvent($event),
            BudgetEnvelopeTargetedAmountChangedDomainEvent::class => $this->applyBudgetEnvelopeTargetedAmountChangedDomainEvent($event),
            BudgetEnvelopeCurrencyChangedDomainEvent::class => $this->applyBudgetEnvelopeCurrencyChangedDomainEvent($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyBudgetEnvelopeAddedDomainEvent(
        BudgetEnvelopeAddedDomainEvent $budgetEnvelopeAddedDomainEvent,
    ): void {
        $this->uuid = $budgetEnvelopeAddedDomainEvent->aggregateId;
        $this->userUuid = $budgetEnvelopeAddedDomainEvent->userId;
        $this->name = $budgetEnvelopeAddedDomainEvent->name;
        $this->targetedAmount = $budgetEnvelopeAddedDomainEvent->targetedAmount;
        $this->currentAmount = '0.00';
        $this->currency = $budgetEnvelopeAddedDomainEvent->currency;
        $this->createdAt = $budgetEnvelopeAddedDomainEvent->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeAddedDomainEvent->occurredOn);
        $this->isDeleted = false;
    }

    private function applyBudgetEnvelopeRenamedDomainEvent
    (BudgetEnvelopeRenamedDomainEvent $budgetEnvelopeRenamedDomainEvent,
    ): void {
        $this->name = $budgetEnvelopeRenamedDomainEvent->name;
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeRenamedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeCreditedDomainEvent(
        BudgetEnvelopeCreditedDomainEvent $budgetEnvelopeCreditedDomainEvent,
    ): void {
        $this->currentAmount = (string) (
            floatval($this->currentAmount) + floatval($budgetEnvelopeCreditedDomainEvent->creditMoney)
        );
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeCreditedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeDebitedDomainEvent(
        BudgetEnvelopeDebitedDomainEvent $budgetEnvelopeDebitedDomainEvent,
    ): void {
        $this->currentAmount = (string) (
            floatval($this->currentAmount) - floatval($budgetEnvelopeDebitedDomainEvent->debitMoney)
        );
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeDebitedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeDeletedDomainEvent(
        BudgetEnvelopeDeletedDomainEvent $budgetEnvelopeDeletedDomainEvent,
    ): void {
        $this->isDeleted = $budgetEnvelopeDeletedDomainEvent->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeDeletedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeRewoundDomainEvent(
        BudgetEnvelopeRewoundDomainEvent $budgetEnvelopeRewoundDomainEvent,
    ): void {
        $this->targetedAmount = $budgetEnvelopeRewoundDomainEvent->targetedAmount;
        $this->currentAmount = $budgetEnvelopeRewoundDomainEvent->currentAmount;
        $this->name = $budgetEnvelopeRewoundDomainEvent->name;
        $this->currency = $budgetEnvelopeRewoundDomainEvent->currency;
        $this->isDeleted = $budgetEnvelopeRewoundDomainEvent->isDeleted;
        $this->updatedAt = $budgetEnvelopeRewoundDomainEvent->updatedAt;
    }

    private function applyBudgetEnvelopeReplayedDomainEvent(
        BudgetEnvelopeReplayedDomainEvent $budgetEnvelopeReplayedDomainEvent,
    ): void {
        $this->targetedAmount = $budgetEnvelopeReplayedDomainEvent->targetedAmount;
        $this->currentAmount = $budgetEnvelopeReplayedDomainEvent->currentAmount;
        $this->name = $budgetEnvelopeReplayedDomainEvent->name;
        $this->currency = $budgetEnvelopeReplayedDomainEvent->currency;
        $this->isDeleted = $budgetEnvelopeReplayedDomainEvent->isDeleted;
        $this->updatedAt = $budgetEnvelopeReplayedDomainEvent->updatedAt;
    }

    private function applyBudgetEnvelopeTargetedAmountChangedDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $budgetEnvelopeTargetedAmountChangedDomainEvent,
    ): void {
        $this->targetedAmount = $budgetEnvelopeTargetedAmountChangedDomainEvent->targetedAmount;
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeTargetedAmountChangedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeCurrencyChangedDomainEvent(
        BudgetEnvelopeCurrencyChangedDomainEvent $budgetEnvelopeCurrencyChangedDomainEvent,
    ): void {
        $this->currency = $budgetEnvelopeCurrencyChangedDomainEvent->currency;
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeCurrencyChangedDomainEvent->occurredOn);
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'currentAmount' => $this->currentAmount,
            'targetedAmount' => $this->targetedAmount,
            'name' => $this->name,
            'currency' => $this->currency,
        ];
    }
}
