<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Aggregates;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class BudgetEnvelope implements AggregateRootInterface
{
    use DomainEventsCapabilityTrait;

    private BudgetEnvelopeId $budgetEnvelopeId;
    private BudgetEnvelopeUserId $userId;
    private BudgetEnvelopeCurrentAmount $budgetEnvelopeCurrentAmount;
    private BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount;
    private BudgetEnvelopeName $budgetEnvelopeName;
    private BudgetEnvelopeCurrency $budgetEnvelopeCurrency;
    private \DateTime $updatedAt;
    private int $aggregateVersion = 0;
    private bool $isDeleted = false;

    private function __construct()
    {
    }

    public static function create(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeCurrency $budgetEnvelopeCurrency,
    ): self {
        $budgetEnvelopeAddedDomainEvent = new BudgetEnvelopeAddedDomainEvent(
            (string) $budgetEnvelopeId,
            (string) $budgetEnvelopeUserId,
            (string) $budgetEnvelopeName,
            (string) $budgetEnvelopeTargetedAmount,
            (string) $budgetEnvelopeCurrency,
        );
        $aggregate = new self();
        $aggregate->raiseDomainEvents($budgetEnvelopeAddedDomainEvent);

        return $aggregate;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function rename(BudgetEnvelopeName $budgetEnvelopeName, BudgetEnvelopeUserId $budgetEnvelopeUserId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($budgetEnvelopeUserId);
        $budgetEnvelopeRenamedDomainEvent = new BudgetEnvelopeRenamedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $budgetEnvelopeName,
        );
        $this->raiseDomainEvents($budgetEnvelopeRenamedDomainEvent);
    }

    public function credit(
        BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);
        $budgetEnvelopeCreditedDomainEvent = new BudgetEnvelopeCreditedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $budgetEnvelopeCreditMoney,
            (string) $budgetEnvelopeEntryDescription,
        );
        $this->raiseDomainEvents($budgetEnvelopeCreditedDomainEvent);
    }

    public function debit(
        BudgetEnvelopeDebitMoney $budgetEnvelopeDebitMoney,
        BudgetEnvelopeEntryDescription $budgetEnvelopeEntryDescription,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeDebitedDomainEvent = new BudgetEnvelopeDebitedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $budgetEnvelopeDebitMoney,
            (string) $budgetEnvelopeEntryDescription,
        );
        $this->raiseDomainEvents($budgetEnvelopeDebitedDomainEvent);
    }

    public function delete(BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeDeletedDomainEvent = new BudgetEnvelopeDeletedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            true,
        );
        $this->raiseDomainEvents($budgetEnvelopeDeletedDomainEvent);
    }

    public function updateTargetedAmount(
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeTargetedAmountChangedDomainEvent = new BudgetEnvelopeTargetedAmountChangedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $budgetEnvelopeTargetedAmount,
        );
        $this->raiseDomainEvents($budgetEnvelopeTargetedAmountChangedDomainEvent);
    }

    public function changeCurrency(BudgetEnvelopeCurrency $budgetEnvelopeCurrency, BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeCurrencyChangedDomainEvent = new BudgetEnvelopeCurrencyChangedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $budgetEnvelopeCurrency,
        );
        $this->raiseDomainEvents($budgetEnvelopeCurrencyChangedDomainEvent);
    }

    public function rewind(BudgetEnvelopeUserId $userId, \DateTimeImmutable $desiredDateTime): void
    {
        $this->assertOwnership($userId);
        $budgetEnvelopeRewoundDomainEvent = new BudgetEnvelopeRewoundDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $this->budgetEnvelopeName,
            (string) $this->budgetEnvelopeTargetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
            (string) $this->budgetEnvelopeCurrency,
            UtcClock::fromDateTimeToString($this->updatedAt),
            UtcClock::fromImmutableToString($desiredDateTime),
            $this->isDeleted,
        );
        $this->raiseDomainEvents($budgetEnvelopeRewoundDomainEvent);
    }

    public function replay(BudgetEnvelopeUserId $userId): void
    {
        $this->assertOwnership($userId);
        $budgetEnvelopeReplayedDomainEvent = new BudgetEnvelopeReplayedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $this->budgetEnvelopeName,
            (string) $this->budgetEnvelopeTargetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
            (string) $this->budgetEnvelopeCurrency,
            UtcClock::fromDateTimeToString($this->updatedAt),
            $this->isDeleted,
        );
        $this->raiseDomainEvents($budgetEnvelopeReplayedDomainEvent);
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function getBudgetEnvelopeName(): BudgetEnvelopeName
    {
        return $this->budgetEnvelopeName;
    }

    public function setAggregateVersion(int $aggregateVersion): self
    {
        $this->aggregateVersion = $aggregateVersion;

        return $this;
    }

    public function getAggregateId(): string
    {
        return (string) $this->budgetEnvelopeId;
    }

    public function applyBudgetEnvelopeAddedDomainEvent(
        BudgetEnvelopeAddedDomainEvent $budgetEnvelopeAddedDomainEvent,
    ): void {
        $this->budgetEnvelopeId = BudgetEnvelopeId::fromString($budgetEnvelopeAddedDomainEvent->aggregateId);
        $this->userId = BudgetEnvelopeUserId::fromString($budgetEnvelopeAddedDomainEvent->userId);
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($budgetEnvelopeAddedDomainEvent->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $budgetEnvelopeAddedDomainEvent->targetedAmount,
            '0.00',
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            '0.00',
            $budgetEnvelopeAddedDomainEvent->targetedAmount,
        );
        $this->budgetEnvelopeCurrency = BudgetEnvelopeCurrency::fromString($budgetEnvelopeAddedDomainEvent->currency);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($budgetEnvelopeAddedDomainEvent->occurredOn);
        $this->isDeleted = false;
    }

    public function applyBudgetEnvelopeRenamedDomainEvent(BudgetEnvelopeRenamedDomainEvent $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->updatedAt = UtcClock::fromImmutableToDateTime($event->occurredOn);
    }

    public function applyBudgetEnvelopeCreditedDomainEvent(
        BudgetEnvelopeCreditedDomainEvent $budgetEnvelopeCreditedDomainEvent,
    ): void {
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) (
                floatval(
                    (string) $this->budgetEnvelopeCurrentAmount
                ) + floatval(
                    $budgetEnvelopeCreditedDomainEvent->creditMoney,
                )
            ),
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($budgetEnvelopeCreditedDomainEvent->occurredOn);
    }

    public function applyBudgetEnvelopeDebitedDomainEvent(
        BudgetEnvelopeDebitedDomainEvent $budgetEnvelopeDebitedDomainEvent,
    ): void {
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) (
                floatval(
                    (string) $this->budgetEnvelopeCurrentAmount,
                ) - floatval(
                    $budgetEnvelopeDebitedDomainEvent->debitMoney,
                )
            ),
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($budgetEnvelopeDebitedDomainEvent->occurredOn);
    }

    public function applyBudgetEnvelopeDeletedDomainEvent(
        BudgetEnvelopeDeletedDomainEvent $budgetEnvelopeDeletedDomainEvent,
    ): void {
        $this->isDeleted = $budgetEnvelopeDeletedDomainEvent->isDeleted;
        $this->updatedAt = UtcClock::fromImmutableToDateTime($budgetEnvelopeDeletedDomainEvent->occurredOn);
    }

    public function applyBudgetEnvelopeTargetedAmountChangedDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $budgetEnvelopeTargetedAmountChangedDomainEvent,
    ): void {
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $budgetEnvelopeTargetedAmountChangedDomainEvent->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($budgetEnvelopeTargetedAmountChangedDomainEvent->occurredOn);
    }

    public function applyBudgetEnvelopeCurrencyChangedDomainEvent(
        BudgetEnvelopeCurrencyChangedDomainEvent $budgetEnvelopeCurrencyChangedDomainEvent,
    ): void {
        $this->budgetEnvelopeCurrency = BudgetEnvelopeCurrency::fromString(
            $budgetEnvelopeCurrencyChangedDomainEvent->currency,
        );
        $this->updatedAt = UtcClock::fromImmutableToDateTime($budgetEnvelopeCurrencyChangedDomainEvent->occurredOn);
    }

    public function applyBudgetEnvelopeReplayedDomainEvent(
        BudgetEnvelopeReplayedDomainEvent $budgetEnvelopeReplayedDomainEvent,
    ): void {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($budgetEnvelopeReplayedDomainEvent->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $budgetEnvelopeReplayedDomainEvent->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            $budgetEnvelopeReplayedDomainEvent->currentAmount,
            $budgetEnvelopeReplayedDomainEvent->targetedAmount,
        );
        $this->updatedAt = UtcClock::fromDatetime($budgetEnvelopeReplayedDomainEvent->updatedAt);
        $this->isDeleted = $budgetEnvelopeReplayedDomainEvent->isDeleted;
    }

    public function applyBudgetEnvelopeRewoundDomainEvent(
        BudgetEnvelopeRewoundDomainEvent $budgetEnvelopeRewoundDomainEvent,
    ): void {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($budgetEnvelopeRewoundDomainEvent->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $budgetEnvelopeRewoundDomainEvent->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            $budgetEnvelopeRewoundDomainEvent->currentAmount,
            $budgetEnvelopeRewoundDomainEvent->targetedAmount,
        );
        $this->updatedAt = UtcClock::fromDatetime($budgetEnvelopeRewoundDomainEvent->updatedAt);
        $this->isDeleted = $budgetEnvelopeRewoundDomainEvent->isDeleted;
    }

    private function assertOwnership(BudgetEnvelopeUserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw BudgetEnvelopeIsNotOwnedByUserException::isNotOwnedByUser();
        }
    }

    private function assertNotDeleted(): void
    {
        if ($this->isDeleted) {
            throw InvalidBudgetEnvelopeOperationException::operationOnDeletedEnvelope();
        }
    }
}
