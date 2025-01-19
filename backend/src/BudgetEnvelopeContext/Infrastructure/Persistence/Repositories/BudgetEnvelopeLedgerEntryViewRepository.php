<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Persistence\Repositories;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewRepositoryInterface;
use Doctrine\DBAL\Connection;

final class BudgetEnvelopeLedgerEntryViewRepository implements BudgetEnvelopeLedgerEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[\Override]
    public function save(BudgetEnvelopeLedgerEntryViewInterface $budgetEnvelopeLedgerEntryView): void
    {
        $this->connection->executeStatement('
        INSERT INTO budget_envelope_ledger_entry_view (budget_envelope_uuid, created_at, monetary_amount, entry_type, user_uuid)
        VALUES (:budget_envelope_uuid, :created_at, :monetary_amount, :entry_type, :user_uuid)
        ON DUPLICATE KEY UPDATE
            created_at = VALUES(created_at),
            monetary_amount = VALUES(monetary_amount),
            entry_type = VALUES(entry_type),
            user_uuid = VALUES(user_uuid)
    ', [
            'budget_envelope_uuid' => $budgetEnvelopeLedgerEntryView->budgetEnvelopeUuid,
            'created_at' => $budgetEnvelopeLedgerEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'monetary_amount' => $budgetEnvelopeLedgerEntryView->monetaryAmount,
            'entry_type' => $budgetEnvelopeLedgerEntryView->entryType,
            'user_uuid' => $budgetEnvelopeLedgerEntryView->userUuid,
        ]);
    }

    #[\Override]
    public function delete(string $budgetEnvelopeUuid): void
    {
        $this->connection->delete('budget_envelope_ledger_entry_view', ['budget_envelope_uuid' => $budgetEnvelopeUuid]);
    }
}
