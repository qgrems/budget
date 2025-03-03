<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Domain\Ports\Inbound;

interface BudgetPlanCategoriesTranslatorInterface
{
    public function translate(array $budgetPlans, string $locale): array;
}
