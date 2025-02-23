<?php

namespace App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs;

final readonly class ListBudgetEnvelopesInput
{
    /**
     * @param array<string, string>|null $orderBy
     */
    public function __construct(
        private(set) ?array $orderBy = null,
        private(set) ?int $limit = null,
        private(set) ?int $offset = null,
    ) {
    }
}
