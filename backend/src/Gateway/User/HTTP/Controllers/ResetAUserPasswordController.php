<?php

declare(strict_types=1);

namespace App\Gateway\User\HTTP\Controllers;

use App\Gateway\User\HTTP\DTOs\ResetAUserPasswordInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Application\Commands\ResetAUserPasswordCommand;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;
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
                UserPasswordResetToken::fromString($resetAUserPasswordDto->token),
                UserPassword::fromString($resetAUserPasswordDto->newPassword),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
