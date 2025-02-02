<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Aggregates;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
use App\SharedContext\Domain\Traits\DomainEventsCapabilityTrait;

final class BudgetEnvelope
{
    use DomainEventsCapabilityTrait;

    private BudgetEnvelopeId $budgetEnvelopeId;
    private BudgetEnvelopeUserId $userId;
    private BudgetEnvelopeCurrentAmount $budgetEnvelopeCurrentAmount;
    private BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount;
    private BudgetEnvelopeName $budgetEnvelopeName;
    private \DateTime $updatedAt;
    private \DateTimeImmutable $addedAt;
    private bool $isDeleted;

    private function __construct()
    {
    }

    public static function fromEvents(\Generator $events): self
    {
        $aggregate = new self();

        foreach ($events as $event) {
            $aggregate->apply($event['type']::fromArray(json_decode($event['payload'], true)));
        }

        return $aggregate;
    }

    public static function create(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ): self {
        if ($budgetEnvelopeViewRepository->findOneBy([
            'user_uuid' => (string) $budgetEnvelopeUserId,
            'name' => (string) $budgetEnvelopeName,
            'is_deleted' => false,
        ])) {
            throw new BudgetEnvelopeNameAlreadyExistsForUserException();
        }

        $budgetEnvelopeAddedDomainEvent = new BudgetEnvelopeAddedDomainEvent(
            (string) $budgetEnvelopeId,
            (string) $budgetEnvelopeUserId,
            (string) $budgetEnvelopeName,
            (string) $budgetEnvelopeTargetedAmount,
        );
        $aggregate = new self();
        $aggregate->apply($budgetEnvelopeAddedDomainEvent);
        $aggregate->raiseDomainEvents($budgetEnvelopeAddedDomainEvent);

        return $aggregate;
    }

    public function rename(
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($budgetEnvelopeUserId);
        $budgetEnvelope = $budgetEnvelopeViewRepository->findOneBy(
            [
                'user_uuid' => (string) $budgetEnvelopeUserId,
                'name' => (string) $budgetEnvelopeName,
                'is_deleted' => false,
            ],
        );

        if (
            $budgetEnvelope instanceof BudgetEnvelopeViewInterface
            && $budgetEnvelope->uuid !== (string) $budgetEnvelopeId
        ) {
            throw new BudgetEnvelopeNameAlreadyExistsForUserException();
        }

        $budgetEnvelopeRenamedDomainEvent = new BudgetEnvelopeRenamedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $budgetEnvelopeName,
        );
        $this->apply($budgetEnvelopeRenamedDomainEvent);
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

        $this->apply($budgetEnvelopeCreditedDomainEvent);
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

        $this->apply($budgetEnvelopeDebitedDomainEvent);
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

        $this->apply($budgetEnvelopeDeletedDomainEvent);
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

        $this->apply($budgetEnvelopeTargetedAmountChangedDomainEvent);
        $this->raiseDomainEvents($budgetEnvelopeTargetedAmountChangedDomainEvent);
    }

    public function rewind(
        BudgetEnvelopeUserId $userId,
        \DateTimeImmutable $desiredDateTime,
    ): void {
        $this->assertOwnership($userId);
        $budgetEnvelopeRewoundDomainEvent = new BudgetEnvelopeRewoundDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $this->budgetEnvelopeName,
            (string) $this->budgetEnvelopeTargetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
            $this->updatedAt->format(\DateTimeInterface::ATOM),
            $desiredDateTime->format(\DateTimeInterface::ATOM),
            $this->isDeleted,
        );
        $this->apply($budgetEnvelopeRewoundDomainEvent);
        $this->raiseDomainEvents($budgetEnvelopeRewoundDomainEvent);
    }

    public function replay(
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertOwnership($userId);
        $budgetEnvelopeReplayedDomainEvent = new BudgetEnvelopeReplayedDomainEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $this->budgetEnvelopeName,
            (string) $this->budgetEnvelopeTargetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
            $this->updatedAt->format(\DateTimeInterface::ATOM),
            $this->isDeleted,
        );
        $this->apply($budgetEnvelopeReplayedDomainEvent);
        $this->raiseDomainEvents($budgetEnvelopeReplayedDomainEvent);
    }

    private function apply(DomainEventInterface $event): void
    {
        match ($event::class) {
            BudgetEnvelopeAddedDomainEvent::class => $this->applyBudgetEnvelopeAddedDomainEvent($event),
            BudgetEnvelopeRenamedDomainEvent::class => $this->applyBudgetEnvelopeRenamedDomainEvent($event),
            BudgetEnvelopeCreditedDomainEvent::class => $this->applyBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->applyBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeDeletedDomainEvent::class => $this->applyBudgetEnvelopeDeletedDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->applyBudgetEnvelopeReplayedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->applyBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeTargetedAmountChangedDomainEvent::class => $this->applyBudgetEnvelopeTargetedAmountChangedDomainEvent($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyBudgetEnvelopeAddedDomainEvent(
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
        $this->addedAt = $budgetEnvelopeAddedDomainEvent->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeAddedDomainEvent->occurredOn);
        $this->isDeleted = false;
    }

    private function applyBudgetEnvelopeRenamedDomainEvent(BudgetEnvelopeRenamedDomainEvent $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyBudgetEnvelopeCreditedDomainEvent(
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
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeCreditedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeDebitedDomainEvent(
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
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeDebitedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeDeletedDomainEvent(
        BudgetEnvelopeDeletedDomainEvent $budgetEnvelopeDeletedDomainEvent,
    ): void {
        $this->isDeleted = $budgetEnvelopeDeletedDomainEvent->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeDeletedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeTargetedAmountChangedDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $budgetEnvelopeTargetedAmountChangedDomainEvent,
    ): void {
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $budgetEnvelopeTargetedAmountChangedDomainEvent->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->updatedAt = \DateTime::createFromImmutable($budgetEnvelopeTargetedAmountChangedDomainEvent->occurredOn);
    }

    private function applyBudgetEnvelopeReplayedDomainEvent(
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
        $this->isDeleted = $budgetEnvelopeReplayedDomainEvent->isDeleted;
    }

    private function applyBudgetEnvelopeRewoundDomainEvent(
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
