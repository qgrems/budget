<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Application\Handlers\QueryHandlers;

use App\EnvelopeManagement\Application\Queries\ListEnvelopesQuery;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\EnvelopeManagement\ReadModels\Views\EnvelopesPaginatedInterface;

final readonly class ListEnvelopesQueryHandler
{
    public function __construct(
        private EnvelopeViewRepositoryInterface $envelopesRepository,
    ) {
    }

    public function __invoke(ListEnvelopesQuery $listEnvelopesQuery): EnvelopesPaginatedInterface
    {
        return $this->envelopesRepository->findBy(
            [
                'user_uuid' => $listEnvelopesQuery->getUserUuid(),
                'is_deleted' => false,
            ],
            $listEnvelopesQuery->getOrderBy(),
            $listEnvelopesQuery->getLimit(),
            $listEnvelopesQuery->getOffset(),
        );
    }
}
