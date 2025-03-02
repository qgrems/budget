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

#[Route('/api/incomes-categories', name: 'app_budget_plans_incomes_categories_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListIncomesCategoriesController
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
                ['id' => 'salary', 'name' => $this->translator->trans('incomes.salary', [], 'messages', $locale)],
                ['id' => 'freelance', 'name' => $this->translator->trans('incomes.freelance', [], 'messages', $locale)],
                ['id' => 'business', 'name' => $this->translator->trans('incomes.business', [], 'messages', $locale)],
                ['id' => 'rental-income', 'name' => $this->translator->trans('incomes.rental-income', [], 'messages', $locale)],
                ['id' => 'investments', 'name' => $this->translator->trans('incomes.investments', [], 'messages', $locale)],
                ['id' => 'government-benefits', 'name' => $this->translator->trans('incomes.government-benefits', [], 'messages', $locale)],
                ['id' => 'pension', 'name' => $this->translator->trans('incomes.pension', [], 'messages', $locale)],
                ['id' => 'side-hustle', 'name' => $this->translator->trans('incomes.side-hustle', [], 'messages', $locale)],
                ['id' => 'child-support', 'name' => $this->translator->trans('incomes.child-support', [], 'messages', $locale)],
                ['id' => 'other', 'name' => $this->translator->trans('incomes.other', [], 'messages', $locale)],
            ], Response::HTTP_OK,
        );
    }
}
