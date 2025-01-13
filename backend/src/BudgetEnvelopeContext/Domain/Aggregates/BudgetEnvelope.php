<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Aggregates;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\SharedContext\Domain\Traits\EventsCapabilityTrait;

final class BudgetEnvelope
{
    use EventsCapabilityTrait;

    private BudgetEnvelopeId $budgetEnvelopeId;
    private BudgetEnvelopeUserId $userId;
    private BudgetEnvelopeCurrentAmount $budgetEnvelopeCurrentAmount;
    private BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount;
    private BudgetEnvelopeName $budgetEnvelopeName;
    private \DateTime $updatedAt;
    private \DateTimeImmutable $createdAt;
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

        $budgetEnvelopeCreatedEvent = new BudgetEnvelopeCreatedEvent(
            (string) $budgetEnvelopeId,
            (string) $budgetEnvelopeUserId,
            (string) $budgetEnvelopeName,
            (string) $budgetEnvelopeTargetedAmount,
        );
        $aggregate = new self();
        $aggregate->apply($budgetEnvelopeCreatedEvent);
        $aggregate->raise($budgetEnvelopeCreatedEvent);

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

        $budgetEnvelopeRenamedEvent = new BudgetEnvelopeRenamedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeName,
        );
        $this->apply($budgetEnvelopeRenamedEvent);
        $this->raise($budgetEnvelopeRenamedEvent);
    }

    public function credit(
        BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeCreditedEvent = new BudgetEnvelopeCreditedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeCreditMoney,
        );

        $this->apply($budgetEnvelopeCreditedEvent);
        $this->raise($budgetEnvelopeCreditedEvent);
    }

    public function debit(
        BudgetEnvelopeDebitMoney $budgetEnvelopeDebitMoney,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeDebitedEvent = new BudgetEnvelopeDebitedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeDebitMoney,
        );

        $this->apply($budgetEnvelopeDebitedEvent);
        $this->raise($budgetEnvelopeDebitedEvent);
    }

    public function delete(BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeDeletedEvent = new BudgetEnvelopeDeletedEvent(
            (string) $this->budgetEnvelopeId,
            true,
        );

        $this->apply($budgetEnvelopeDeletedEvent);
        $this->raise($budgetEnvelopeDeletedEvent);
    }

    public function updateTargetedAmount(
        BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount,
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeTargetedAmountUpdatedEvent = new BudgetEnvelopeTargetedAmountUpdatedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeTargetedAmount,
        );

        $this->apply($budgetEnvelopeTargetedAmountUpdatedEvent);
        $this->raise($budgetEnvelopeTargetedAmountUpdatedEvent);
    }

    public function rewind(
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertOwnership($userId);
        $budgetEnvelopeRewoundEvent = new BudgetEnvelopeRewoundEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $this->budgetEnvelopeName,
            (string) $this->budgetEnvelopeTargetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
            $this->updatedAt->format(\DateTimeInterface::ATOM),
            $this->isDeleted,
        );
        $this->apply($budgetEnvelopeRewoundEvent);
        $this->raise($budgetEnvelopeRewoundEvent);
    }

    public function replay(
        BudgetEnvelopeUserId $userId,
    ): void {
        $this->assertOwnership($userId);
        $budgetEnvelopeReplayedEvent = new BudgetEnvelopeReplayedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $this->userId,
            (string) $this->budgetEnvelopeName,
            (string) $this->budgetEnvelopeTargetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
            $this->updatedAt->format(\DateTimeInterface::ATOM),
            $this->isDeleted,
        );
        $this->raise($budgetEnvelopeReplayedEvent);
        $this->apply($budgetEnvelopeReplayedEvent);
    }

    private function apply(EventInterface $event): void
    {
        match ($event::class) {
            BudgetEnvelopeCreatedEvent::class => $this->applyCreatedEvent($event),
            BudgetEnvelopeRenamedEvent::class => $this->applyRenamedEvent($event),
            BudgetEnvelopeCreditedEvent::class => $this->applyCreditedEvent($event),
            BudgetEnvelopeDebitedEvent::class => $this->applyDebitedEvent($event),
            BudgetEnvelopeDeletedEvent::class => $this->applyDeletedEvent($event),
            BudgetEnvelopeReplayedEvent::class => $this->applyReplayedEvent($event),
            BudgetEnvelopeRewoundEvent::class => $this->applyRewoundEvent($event),
            BudgetEnvelopeTargetedAmountUpdatedEvent::class => $this->applyTargetedAmountUpdatedEvent($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyCreatedEvent(BudgetEnvelopeCreatedEvent $event): void
    {
        $this->budgetEnvelopeId = BudgetEnvelopeId::fromString($event->aggregateId);
        $this->userId = BudgetEnvelopeUserId::fromString($event->userId);
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            '0.00',
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            '0.00',
            $event->targetedAmount,
        );
        $this->createdAt = $event->occurredOn;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->isDeleted = false;
    }

    private function applyRenamedEvent(BudgetEnvelopeRenamedEvent $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyCreditedEvent(BudgetEnvelopeCreditedEvent $event): void
    {
        $newBudget = (floatval((string) $this->budgetEnvelopeCurrentAmount) + floatval($event->creditMoney));

        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) $newBudget,
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyDebitedEvent(BudgetEnvelopeDebitedEvent $event): void
    {
        $newBudget = (floatval((string) $this->budgetEnvelopeCurrentAmount) - floatval($event->debitMoney));

        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) $newBudget,
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyDeletedEvent(BudgetEnvelopeDeletedEvent $event): void
    {
        $this->isDeleted = $event->isDeleted;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyTargetedAmountUpdatedEvent(BudgetEnvelopeTargetedAmountUpdatedEvent $event): void
    {
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyReplayedEvent(BudgetEnvelopeReplayedEvent $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            $event->currentAmount,
            $event->targetedAmount,
        );
        $this->isDeleted = $event->isDeleted;
    }

    private function applyRewoundEvent(BudgetEnvelopeRewoundEvent $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->name);
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->targetedAmount,
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            $event->currentAmount,
            $event->targetedAmount,
        );
        $this->isDeleted = $event->isDeleted;
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
