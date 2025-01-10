<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Infrastructure\Persistence\Repositories;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewInterface;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewRepositoryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class BudgetEnvelopeHistoryViewRepository implements BudgetEnvelopeHistoryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function save(BudgetEnvelopeHistoryViewInterface $budgetEnvelopeHistory): void
    {
        $this->connection->executeStatement('
        INSERT INTO budget_envelope_history_view (aggregate_id, created_at, monetary_amount, transaction_type, user_uuid)
        VALUES (:aggregate_id, :created_at, :monetary_amount, :transaction_type, :user_uuid)
        ON DUPLICATE KEY UPDATE
            created_at = VALUES(created_at),
            monetary_amount = VALUES(monetary_amount),
            transaction_type = VALUES(transaction_type),
            user_uuid = VALUES(user_uuid)
    ', [
            'aggregate_id' => $budgetEnvelopeHistory->getAggregateId(),
            'created_at' => $budgetEnvelopeHistory->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            'monetary_amount' => $budgetEnvelopeHistory->getMonetaryAmount(),
            'transaction_type' => $budgetEnvelopeHistory->getTransactionType(),
            'user_uuid' => $budgetEnvelopeHistory->getUserUuid(),
        ]);
    }
}
