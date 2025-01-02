<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\ResetAUserPasswordCommand;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Presentation\HTTP\DTOs\ResetAUserPasswordInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users/reset-password', name: 'app_user_reset_password', methods: ['POST'])]
final readonly class ResetAUserPasswordController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[MapRequestPayload] ResetAUserPasswordInput $resetAUserPasswordDto,
    ): JsonResponse {
        $this->commandBus->execute(
            new ResetAUserPasswordCommand(
                $resetAUserPasswordDto->getToken(),
                $resetAUserPasswordDto->getNewPassword(),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
