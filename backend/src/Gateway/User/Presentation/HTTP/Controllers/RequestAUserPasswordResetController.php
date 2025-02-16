<?php

declare(strict_types=1);

namespace App\Gateway\User\Presentation\HTTP\Controllers;

use App\Gateway\User\Presentation\HTTP\DTOs\RequestAUserPasswordResetInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Application\Commands\RequestAUserPasswordResetCommand;
use App\UserContext\Domain\ValueObjects\UserEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users/request-password-reset', name: 'app_user_request_password_reset', methods: ['POST'])]
final readonly class RequestAUserPasswordResetController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[MapRequestPayload] RequestAUserPasswordResetInput $requestAPasswordResetInput,
    ): JsonResponse {
        $this->commandBus->execute(new RequestAUserPasswordResetCommand(
            UserEmail::fromString($requestAPasswordResetInput->email),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
