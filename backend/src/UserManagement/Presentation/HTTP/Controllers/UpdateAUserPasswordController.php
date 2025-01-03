<?php

declare(strict_types=1);

namespace App\UserManagement\Presentation\HTTP\Controllers;

use App\UserManagement\Application\Commands\UpdateAUserPasswordCommand;
use App\UserManagement\Domain\Ports\Outbound\CommandBusInterface;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserPassword;
use App\UserManagement\Presentation\HTTP\DTOs\UpdateAUserPasswordInput;
use App\UserManagement\ReadModels\Views\UserView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/change-password', name: 'app_user_change_password', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class UpdateAUserPasswordController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserView $currentUser,
        #[MapRequestPayload] UpdateAUserPasswordInput $updateAUserPasswordInput,
    ): JsonResponse {
        $this->commandBus->execute(new UpdateAUserPasswordCommand(
            UserPassword::fromString($updateAUserPasswordInput->oldPassword),
            UserPassword::fromString($updateAUserPasswordInput->newPassword),
            UserId::fromString($currentUser->getUuid()),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
