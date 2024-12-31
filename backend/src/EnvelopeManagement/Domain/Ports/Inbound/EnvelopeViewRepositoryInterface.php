<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Domain\Ports\Inbound;

use App\EnvelopeManagement\ReadModels\Views\EnvelopesPaginatedInterface;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeViewInterface;

interface EnvelopeViewRepositoryInterface
{
    public function save(EnvelopeViewInterface $envelope): void;

    public function delete(EnvelopeViewInterface $envelope): void;

    public function findOneBy(array $criteria, ?array $orderBy = null): ?EnvelopeViewInterface;

    public function findOneEnvelopeWithHistoryBy(array $criteria, ?array $orderBy = null): array;

    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): EnvelopesPaginatedInterface;
}
