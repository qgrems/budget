<?php

declare(strict_types=1);

namespace App\UserContext\Presentation\HTTP\Controllers;

use App\UserContext\Application\Commands\UpdateAUserLanguagePreferenceCommand;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Presentation\HTTP\DTOs\UpdateAUserLanguagePreferenceInput;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users/language-preference', name: 'app_user_edit_language_preference', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
final readonly class UpdateAUserLanguagePreferenceController
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(
        #[CurrentUser] UserViewInterface $currentUser,
        #[MapRequestPayload] UpdateAUserLanguagePreferenceInput $updateAUserLanguagePreferenceInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new UpdateAUserLanguagePreferenceCommand(
                UserId::fromString($currentUser->getUuid()),
                UserLanguagePreference::fromString($updateAUserLanguagePreferenceInput->languagePreference),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
