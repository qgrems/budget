<?php

namespace App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs;

final readonly class ListBudgetEnvelopesInput
{
    /**
     * @param array<string, string>|null $orderBy
     */
    public function __construct(
        public ?array $orderBy = null,
        public ?int $limit = null,
        public ?int $offset = null,
    ) {
    }
}
