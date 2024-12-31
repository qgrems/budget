<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Application\Handlers\QueryHandlers;

use App\EnvelopeManagement\Application\Queries\ShowEnvelopeQuery;
use App\EnvelopeManagement\Domain\Exceptions\EnvelopeNotFoundException;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeViewInterface;

final readonly class ShowEnvelopeQueryHandler
{
    public function __construct(
        private EnvelopeViewRepositoryInterface $envelopeViewRepository,
    ) {
    }

    /**
     * @throws EnvelopeNotFoundException
     */
    public function __invoke(ShowEnvelopeQuery $getOneEnvelopeQuery): array
    {
        $envelope = $this->envelopeViewRepository->findOneEnvelopeWithHistoryBy([
            'uuid' => $getOneEnvelopeQuery->getEnvelopeUuid(),
            'user_uuid' => $getOneEnvelopeQuery->getUserUuid(),
            'is_deleted' => false,
        ]);

        if ($envelope === []) {
            throw new EnvelopeNotFoundException(EnvelopeNotFoundException::MESSAGE, 404);
        }

        return $envelope;
    }
}
