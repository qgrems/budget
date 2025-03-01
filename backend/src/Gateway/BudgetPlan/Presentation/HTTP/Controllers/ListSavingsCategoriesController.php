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

#[Route('/api/savings-categories', name: 'app_budget_plans_savings_categories_listing', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final readonly class ListSavingsCategoriesController
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
                ['id' => 'emergency-fund', 'name' => $this->translator->trans('savings.emergency-fund', [], 'messages', $locale)],
                ['id' => 'retirement', 'name' => $this->translator->trans('savings.retirement', [], 'messages', $locale)],
                ['id' => 'vacation', 'name' => $this->translator->trans('savings.vacation', [], 'messages', $locale)],
                ['id' => 'education', 'name' => $this->translator->trans('savings.education', [], 'messages', $locale)],
                ['id' => 'home-purchase', 'name' => $this->translator->trans('savings.home-purchase', [], 'messages', $locale)],
                ['id' => 'investment', 'name' => $this->translator->trans('savings.investment', [], 'messages', $locale)],
                ['id' => 'healthcare', 'name' => $this->translator->trans('savings.healthcare', [], 'messages', $locale)],
                ['id' => 'debt-repayment', 'name' => $this->translator->trans('savings.debt-repayment', [], 'messages', $locale)],
                ['id' => 'charitable-giving', 'name' => $this->translator->trans('savings.charitable-giving', [], 'messages', $locale)],
            ], Response::HTTP_OK,
        );
    }
}
