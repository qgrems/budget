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

#[Route('/api/needs-categories', name: 'app_budget_plans_needs_categories_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListNeedsCategoriesController
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
                ['id' => 'rent', 'name' => $this->translator->trans('needs.rent', [], 'messages', $locale)],
                ['id' => 'mortgage', 'name' => $this->translator->trans('needs.mortgage', [], 'messages', $locale)],
                ['id' => 'utilities', 'name' => $this->translator->trans('needs.utilities', [], 'messages', $locale)],
                ['id' => 'insurance', 'name' => $this->translator->trans('needs.insurance', [], 'messages', $locale)],
                ['id' => 'food', 'name' => $this->translator->trans('needs.food', [], 'messages', $locale)],
                ['id' => 'transportation', 'name' => $this->translator->trans('needs.transportation', [], 'messages', $locale)],
                ['id' => 'healthcare', 'name' => $this->translator->trans('needs.healthcare', [], 'messages', $locale)],
                ['id' => 'debt-repayment', 'name' => $this->translator->trans('needs.debt-repayment', [], 'messages', $locale)],
            ], Response::HTTP_OK,
        );
    }
}
