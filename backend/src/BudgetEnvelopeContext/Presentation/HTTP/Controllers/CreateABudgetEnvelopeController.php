<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Presentation\HTTP\Controllers;

use App\BudgetEnvelopeContext\Application\Commands\CreateABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Ports\Outbound\CommandBusInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\CreateABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/envelopes/new', name: 'app_budget_envelope_new', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class CreateABudgetEnvelopeController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    public function __invoke(
        #[MapRequestPayload] CreateABudgetEnvelopeInput $createABudgetEnvelopeInput,
        #[CurrentUser] SharedUserInterface $user,
    ): JsonResponse {
        $this->commandBus->execute(
            new CreateABudgetEnvelopeCommand(
                BudgetEnvelopeId::fromString($createABudgetEnvelopeInput->uuid),
                BudgetEnvelopeUserId::fromString($user->getUuid()),
                BudgetEnvelopeName::fromString($createABudgetEnvelopeInput->name),
                BudgetEnvelopeTargetedAmount::fromString(
                    $createABudgetEnvelopeInput->targetedAmount,
                    '0.00',
                ),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
