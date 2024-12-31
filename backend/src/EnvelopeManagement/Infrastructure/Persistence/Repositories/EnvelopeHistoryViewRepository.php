<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Infrastructure\Persistence\Repositories;

use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeHistoryViewRepositoryInterface;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeHistoryViewInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class EnvelopeHistoryViewRepository implements EnvelopeHistoryViewRepositoryInterface
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
    public function save(EnvelopeHistoryViewInterface $envelopeHistory): void
    {
        $this->connection->executeStatement('
        INSERT INTO envelope_history_view (aggregate_id, created_at, monetary_amount, transaction_type, user_uuid)
        VALUES (:aggregate_id, :created_at, :monetary_amount, :transaction_type, :user_uuid)
        ON DUPLICATE KEY UPDATE
            created_at = VALUES(created_at),
            monetary_amount = VALUES(monetary_amount),
            transaction_type = VALUES(transaction_type),
            user_uuid = VALUES(user_uuid)
    ', [
            'aggregate_id' => $envelopeHistory->getAggregateId(),
            'created_at' => $envelopeHistory->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            'monetary_amount' => $envelopeHistory->getMonetaryAmount(),
            'transaction_type' => $envelopeHistory->getTransactionType(),
            'user_uuid' => $envelopeHistory->getUserUuid(),
        ]);
    }
}
