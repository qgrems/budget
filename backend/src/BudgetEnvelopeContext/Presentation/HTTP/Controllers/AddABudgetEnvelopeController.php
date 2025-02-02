<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\AddABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/add', name: 'app_budget_envelope_add', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class AddABudgetEnvelopeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] AddABudgetEnvelopeInput $addABudgetEnvelopeInput,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new AddABudgetEnvelopeCommand(
                BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
                BudgetEnvelopeUserId::fromString($user->getUuid()),
                BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
                BudgetEnvelopeTargetedAmount::fromString(
                    $addABudgetEnvelopeInput->targetedAmount,
                    '0.00',
                ),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
