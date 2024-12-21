<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Application\Handlers\QueryHandlers;

use App\EnvelopeManagement\Application\Queries\GetEnvelopeByTitleQuery;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeViewInterface;

final readonly class GetEnvelopeByTitleQueryHandler
{
    public function __construct(
        private EnvelopeViewRepositoryInterface $envelopeViewRepository,
    ) {
    }

    public function __invoke(GetEnvelopeByTitleQuery $getOneEnvelopeQuery): ?EnvelopeViewInterface
    {
        return $this->envelopeViewRepository->findOneBy([
            'title' => $getOneEnvelopeQuery->getTitle(),
            'user_uuid' => $getOneEnvelopeQuery->getUserUuid(),
            'is_deleted' => false,
        ]);
    }
}
