<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

interface BudgetEnvelopeLedgerEntryViewRepositoryInterface
{
    public function save(BudgetEnvelopeLedgerEntryViewInterface $budgetEnvelopeLedgerEntryView): void;

    public function delete(string $budgetEnvelopeUuid): void;
}
