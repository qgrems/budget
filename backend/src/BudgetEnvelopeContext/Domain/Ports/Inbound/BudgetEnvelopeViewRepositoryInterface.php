<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

interface BudgetEnvelopeViewRepositoryInterface
{
    public function save(BudgetEnvelopeViewInterface $budgetEnvelope): void;

    public function delete(BudgetEnvelopeViewInterface $budgetEnvelope): void;

    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetEnvelopeViewInterface;

    public function findOneEnvelopeWithItsLedgerBy(array $criteria, ?array $orderBy = null): array;

    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
    ): BudgetEnvelopesPaginatedInterface;
}
