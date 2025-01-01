<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeManagement\Application\Commands\RenameABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\RenameABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/{uuid}/name', name: 'app_budget_envelope_rename', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class RenameABudgetEnvelopeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] RenameABudgetEnvelopeInput $renameABudgetEnvelopeInput,
        string $uuid,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new RenameABudgetEnvelopeCommand(
                $renameABudgetEnvelopeInput->getName(),
                $uuid,
                $user->getUuid(),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
