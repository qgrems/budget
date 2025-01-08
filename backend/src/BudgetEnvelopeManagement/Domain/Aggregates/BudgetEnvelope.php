<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Aggregates;

use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeCurrentAmount;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\SharedContext\Traits\EventsCapability;

final class BudgetEnvelope
{
    use EventsCapability;

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
            throw new BudgetEnvelopeNameAlreadyExistsForUserException(BudgetEnvelopeNameAlreadyExistsForUserException::MESSAGE, 400);
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

        if ($budgetEnvelope && $budgetEnvelope->getUuid() !== (string) $budgetEnvelopeId) {
            throw new BudgetEnvelopeNameAlreadyExistsForUserException(BudgetEnvelopeNameAlreadyExistsForUserException::MESSAGE, 400);
        }

        $budgetEnvelopeRenamedEvent = new BudgetEnvelopeRenamedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeName,
        );
        $this->apply($budgetEnvelopeRenamedEvent);
        $this->raise($budgetEnvelopeRenamedEvent);
    }

    public function credit(BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney, BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeCreditedEvent = new BudgetEnvelopeCreditedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeCreditMoney,
        );

        $this->apply($budgetEnvelopeCreditedEvent);
        $this->raise($budgetEnvelopeCreditedEvent);
    }

    public function debit(BudgetEnvelopeDebitMoney $budgetEnvelopeDebitMoney, BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeDeditedEvent = new BudgetEnvelopeDebitedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeDebitMoney,
        );

        $this->apply($budgetEnvelopeDeditedEvent);
        $this->raise($budgetEnvelopeDeditedEvent);
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

    public function updateTargetedAmount(BudgetEnvelopeTargetedAmount $budgetEnvelopeTargetedAmount, BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $budgetEnvelopeTargetedAmountUpdatedEvent = new BudgetEnvelopeTargetedAmountUpdatedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeTargetedAmount,
        );

        $this->apply($budgetEnvelopeTargetedAmountUpdatedEvent);
        $this->raise($budgetEnvelopeTargetedAmountUpdatedEvent);
    }

    private function apply(EventInterface $event): void
    {
        match (get_class($event)) {
            BudgetEnvelopeCreatedEvent::class => $this->applyCreatedEvent($event),
            BudgetEnvelopeRenamedEvent::class => $this->applyNamedEvent($event),
            BudgetEnvelopeCreditedEvent::class => $this->applyCreditedEvent($event),
            BudgetEnvelopeDebitedEvent::class => $this->applyDebitedEvent($event),
            BudgetEnvelopeDeletedEvent::class => $this->applyDeletedEvent($event),
            BudgetEnvelopeTargetedAmountUpdatedEvent::class => $this->applyTargetedAmountUpdatedEvent($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyCreatedEvent(BudgetEnvelopeCreatedEvent $event): void
    {
        $this->budgetEnvelopeId = BudgetEnvelopeId::fromString($event->getAggregateId());
        $this->userId = BudgetEnvelopeUserId::fromString($event->getUserId());
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->getName());
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->getTargetedAmount(),
            '0.00',
        );
        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            '0.00',
            $event->getTargetedAmount(),
        );
        $this->createdAt = $event->occurredOn();
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
        $this->isDeleted = false;
    }

    private function applyNamedEvent(BudgetEnvelopeRenamedEvent $event): void
    {
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->getName());
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyCreditedEvent(BudgetEnvelopeCreditedEvent $event): void
    {
        $newBudget = (floatval((string) $this->budgetEnvelopeCurrentAmount) + floatval($event->getCreditMoney()));

        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) $newBudget,
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyDebitedEvent(BudgetEnvelopeDebitedEvent $event): void
    {
        $newBudget = (floatval((string) $this->budgetEnvelopeCurrentAmount) - floatval($event->getDebitMoney()));

        $this->budgetEnvelopeCurrentAmount = BudgetEnvelopeCurrentAmount::fromString(
            (string) $newBudget,
            (string) $this->budgetEnvelopeTargetedAmount,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyDeletedEvent(BudgetEnvelopeDeletedEvent $event): void
    {
        $this->isDeleted = $event->isDeleted();
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyTargetedAmountUpdatedEvent(BudgetEnvelopeTargetedAmountUpdatedEvent $event): void
    {
        $this->budgetEnvelopeTargetedAmount = BudgetEnvelopeTargetedAmount::fromString(
            $event->getTargetedAmount(),
            (string) $this->budgetEnvelopeCurrentAmount,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function assertOwnership(BudgetEnvelopeUserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw new \RuntimeException('envelopes.notOwner');
        }
    }

    private function assertNotDeleted(): void
    {
        if ($this->isDeleted) {
            throw InvalidBudgetEnvelopeOperationException::operationOnDeletedEnvelope();
        }
    }
}
