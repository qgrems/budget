<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeHistoryTransactionType;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'budget_envelope_history_view')]
#[ORM\Index(name: 'idx_budget_envelope_history_view_budget_envelope_uuid', columns: ['budget_envelope_uuid'])]
final class BudgetEnvelopeHistoryView implements BudgetEnvelopeHistoryViewInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(name: 'budget_envelope_uuid', type: 'string', length: 36, unique: false)]
    private(set) string $budgetEnvelopeUuid;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'monetary_amount', type: 'string', length: 13)]
    private(set) string $monetaryAmount;

    #[ORM\Column(name: 'transaction_type', type: 'string', length: 6)]
    private(set) string $transactionType;

    #[ORM\Column(name: 'user_uuid', type: 'string', length: 36)]
    private(set) string $userUuid;

    private function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeHistoryTransactionType $transactionType,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        \DateTimeImmutable $createdAt,
        string $monetaryAmount,
    ) {
        $this->budgetEnvelopeUuid = (string) $budgetEnvelopeId;
        $this->transactionType = (string) $transactionType;
        $this->userUuid = (string) $budgetEnvelopeUserId;
        $this->createdAt = $createdAt;
        $this->monetaryAmount = $monetaryAmount;
    }

    #[\Override]
    public static function fromRepository(array $budgetEnvelopeHistory): self
    {
        return new self(
            BudgetEnvelopeId::fromString($budgetEnvelopeHistory['aggregate_id']),
            BudgetEnvelopeHistoryTransactionType::fromString($budgetEnvelopeHistory['transaction_type']),
            BudgetEnvelopeUserId::fromString($budgetEnvelopeHistory['user_uuid']),
            new \DateTimeImmutable($budgetEnvelopeHistory['created_at']),
            $budgetEnvelopeHistory['monetary_amount'],
        );
    }

    #[\Override]
    public static function fromBudgetEnvelopeCreditedEvent(BudgetEnvelopeCreditedEvent $budgetEnvelopeCreditedEvent, string $userUuid): self
    {
        return new self(
            BudgetEnvelopeId::fromString($budgetEnvelopeCreditedEvent->aggregateId),
            BudgetEnvelopeHistoryTransactionType::fromString(BudgetEnvelopeHistoryTransactionType::CREDIT),
            BudgetEnvelopeUserId::fromString($userUuid),
            $budgetEnvelopeCreditedEvent->occurredOn,
            $budgetEnvelopeCreditedEvent->creditMoney,
        );
    }

    #[\Override]
    public static function fromBudgetEnvelopeDebitedEvent(BudgetEnvelopeDebitedEvent $event, string $userUuid): self
    {
        return new self(
            BudgetEnvelopeId::fromString($event->aggregateId),
            BudgetEnvelopeHistoryTransactionType::fromString(BudgetEnvelopeHistoryTransactionType::DEBIT),
            BudgetEnvelopeUserId::fromString($userUuid),
            $event->occurredOn,
            $event->debitMoney,
        );
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'monetary_amount' => $this->monetaryAmount,
            'transaction_type' => $this->transactionType,
        ];
    }
}
