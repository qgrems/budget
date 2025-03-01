<?php

declare(strict_types=1);

namespace App\Gateway\BudgetPlan\Presentation\HTTP\Controllers;

use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/wants-categories', name: 'app_budget_plans_wants_categories_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListWantsCategoriesController
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function __invoke(
        #[CurrentUser] UserViewInterface $user,
    ): JsonResponse {
        $locale = $user->languagePreference;

        return new JsonResponse(
            [
                ['id' => 'entertainment', 'name' => $this->translator->trans('wants.entertainment', [], 'messages', $locale)],
                ['id' => 'dining-out', 'name' => $this->translator->trans('wants.dining-out', [], 'messages', $locale)],
                ['id' => 'shopping', 'name' => $this->translator->trans('wants.shopping', [], 'messages', $locale)],
                ['id' => 'gym-membership', 'name' => $this->translator->trans('wants.gym-membership', [], 'messages', $locale)],
                ['id' => 'subscriptions', 'name' => $this->translator->trans('wants.subscriptions', [], 'messages', $locale)],
                ['id' => 'luxury-items', 'name' => $this->translator->trans('wants.luxury-items', [], 'messages', $locale)],
                ['id' => 'hobbies', 'name' => $this->translator->trans('wants.hobbies', [], 'messages', $locale)],
            ], Response::HTTP_OK,
        );
    }
}
