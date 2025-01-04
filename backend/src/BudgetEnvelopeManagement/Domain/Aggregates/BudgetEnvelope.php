<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Aggregates;

use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeCurrentBudget;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeTargetBudget;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final class BudgetEnvelope
{
    private BudgetEnvelopeId $budgetEnvelopeId;
    private BudgetEnvelopeUserId $userId;
    private BudgetEnvelopeCurrentBudget $budgetEnvelopeCurrentBudget;
    private BudgetEnvelopeTargetBudget $budgetEnvelopeTargetBudget;
    private BudgetEnvelopeName $budgetEnvelopeName;
    private \DateTime $updatedAt;
    private \DateTimeImmutable $createdAt;
    private bool $isDeleted;
    private array $uncommittedEvents = [];

    private function __construct()
    {
    }

    public static function fromEvents(array $events): self
    {
        $aggregate = new self();

        foreach ($events as $event) {
            $aggregate->applyEvent($event['type']::fromArray(json_decode($event['payload'], true)));
        }

        return $aggregate;
    }

    public static function create(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        BudgetEnvelopeTargetBudget $budgetEnvelopeTargetBudget,
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

        $aggregate = new self();

        $event = new BudgetEnvelopeCreatedEvent(
            (string) $budgetEnvelopeId,
            (string) $budgetEnvelopeUserId,
            (string) $budgetEnvelopeName,
            (string) $budgetEnvelopeTargetBudget,
        );

        $aggregate->applyEvent($event);
        $aggregate->recordEvent($event);

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

        $event = new BudgetEnvelopeRenamedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeName,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function credit(BudgetEnvelopeCreditMoney $budgetEnvelopeCreditMoney, BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $event = new BudgetEnvelopeCreditedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeCreditMoney,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function debit(BudgetEnvelopeDebitMoney $budgetEnvelopeDebitMoney, BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $event = new BudgetEnvelopeDebitedEvent(
            (string) $this->budgetEnvelopeId,
            (string) $budgetEnvelopeDebitMoney,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function delete(BudgetEnvelopeUserId $userId): void
    {
        $this->assertNotDeleted();
        $this->assertOwnership($userId);

        $event = new BudgetEnvelopeDeletedEvent(
            (string) $this->budgetEnvelopeId,
            true,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    public function clearUncommitedEvent(): void
    {
        $this->uncommittedEvents = [];
    }

    private function applyEvent(EventInterface $event): void
    {
        match (get_class($event)) {
            BudgetEnvelopeCreatedEvent::class => $this->applyCreatedEvent($event),
            BudgetEnvelopeRenamedEvent::class => $this->applyNamedEvent($event),
            BudgetEnvelopeCreditedEvent::class => $this->applyCreditedEvent($event),
            BudgetEnvelopeDebitedEvent::class => $this->applyDebitedEvent($event),
            BudgetEnvelopeDeletedEvent::class => $this->applyDeletedEvent($event),
            default => throw new \RuntimeException('envelopes.unknownEvent'),
        };
    }

    private function applyCreatedEvent(BudgetEnvelopeCreatedEvent $event): void
    {
        $this->budgetEnvelopeId = BudgetEnvelopeId::fromString($event->getAggregateId());
        $this->userId = BudgetEnvelopeUserId::fromString($event->getUserId());
        $this->budgetEnvelopeName = BudgetEnvelopeName::fromString($event->getName());
        $this->budgetEnvelopeTargetBudget = BudgetEnvelopeTargetBudget::fromString($event->getTargetBudget());
        $this->budgetEnvelopeCurrentBudget = BudgetEnvelopeCurrentBudget::fromString(
            '0.00',
            $event->getTargetBudget(),
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
        $newBudget = (floatval((string) $this->budgetEnvelopeCurrentBudget) + floatval($event->getCreditMoney()));

        $this->budgetEnvelopeCurrentBudget = BudgetEnvelopeCurrentBudget::fromString(
            (string) $newBudget,
            (string) $this->budgetEnvelopeTargetBudget,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyDebitedEvent(BudgetEnvelopeDebitedEvent $event): void
    {
        $newBudget = (floatval((string) $this->budgetEnvelopeCurrentBudget) - floatval($event->getDebitMoney()));

        $this->budgetEnvelopeCurrentBudget = BudgetEnvelopeCurrentBudget::fromString(
            (string) $newBudget,
            (string) $this->budgetEnvelopeTargetBudget,
        );
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyDeletedEvent(BudgetEnvelopeDeletedEvent $event): void
    {
        $this->isDeleted = $event->isDeleted();
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

    private function recordEvent(EventInterface $event): void
    {
        $this->uncommittedEvents[] = $event;
    }
}
