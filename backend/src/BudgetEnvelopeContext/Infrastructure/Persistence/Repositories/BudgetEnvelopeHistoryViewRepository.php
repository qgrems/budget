<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Persistence\Repositories;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewRepositoryInterface;
use Doctrine\DBAL\Connection;

final class BudgetEnvelopeHistoryViewRepository implements BudgetEnvelopeHistoryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function save(BudgetEnvelopeHistoryViewInterface $budgetEnvelopeHistoryView): void
    {
        $this->connection->executeStatement('
        INSERT INTO budget_envelope_history_view (budget_envelope_uuid, created_at, monetary_amount, transaction_type, user_uuid)
        VALUES (:budget_envelope_uuid, :created_at, :monetary_amount, :transaction_type, :user_uuid)
        ON DUPLICATE KEY UPDATE
            created_at = VALUES(created_at),
            monetary_amount = VALUES(monetary_amount),
            transaction_type = VALUES(transaction_type),
            user_uuid = VALUES(user_uuid)
    ', [
            'budget_envelope_uuid' => $budgetEnvelopeHistoryView->budgetEnvelopeUuid,
            'created_at' => $budgetEnvelopeHistoryView->createdAt->format(\DateTimeImmutable::ATOM),
            'monetary_amount' => $budgetEnvelopeHistoryView->monetaryAmount,
            'transaction_type' => $budgetEnvelopeHistoryView->transactionType,
            'user_uuid' => $budgetEnvelopeHistoryView->userUuid,
        ]);
    }
}
