<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\SignUpAUserCommand;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Presentation\HTTP\DTOs\SignUpAUserInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users/new', name: 'app_user_sign_up', methods: ['POST'])]
final readonly class SignUpAUserController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[MapRequestPayload] SignUpAUserInput $signUpAUserDto,
    ): JsonResponse {
        $this->commandBus->execute(
            new SignUpAUserCommand(
                $signUpAUserDto->getUuid(),
                $signUpAUserDto->getEmail(),
                $signUpAUserDto->getPassword(),
                $signUpAUserDto->getFirstname(),
                $signUpAUserDto->getLastname(),
                $signUpAUserDto->isConsentGiven(),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
