<?php

declare(strict_types=1);

namespace App\Gateway\User\HTTP\Controllers;

use App\Gateway\User\HTTP\DTOs\SignUpAUserInput;
use App\SharedContext\Domain\Ports\Outbound\CommandBusInterface;
use App\UserContext\Application\Commands\SignUpAUserCommand;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
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
        #[MapRequestPayload] SignUpAUserInput $signUpAUserInput,
    ): JsonResponse {
        $this->commandBus->execute(
            new SignUpAUserCommand(
                UserId::fromString($signUpAUserInput->uuid),
                UserEmail::fromString($signUpAUserInput->email),
                UserPassword::fromString($signUpAUserInput->password),
                UserFirstname::fromString($signUpAUserInput->firstname),
                UserLastname::fromString($signUpAUserInput->lastname),
                UserLanguagePreference::fromString($signUpAUserInput->languagePreference),
                UserConsent::fromBool($signUpAUserInput->consentGiven),
            ),
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
