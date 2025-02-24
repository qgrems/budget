<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\QueryHandlers;

use App\BudgetPlanContext\Application\Queries\ListBudgetPlansCalendarQuery;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;

final readonly class ListBudgetPlansCalendarQueryHandler
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
    ) {
    }

    public function __invoke(ListBudgetPlansCalendarQuery $listBudgetPlansCalendarQuery): array
    {
        $budgetPlans = $this->budgetPlanViewRepository->findBy(
            [
                'user_uuid' => (string) $listBudgetPlansCalendarQuery->getBudgetPlanUserId(),
                'is_deleted' => false,
            ],
        );

        return $this->generateCalendarFromBudgetPlans($budgetPlans);
    }

    private function generateCalendarFromBudgetPlans(array $budgetPlans): array {
        $currentYear = (int) new \DateTimeImmutable()->format('Y');
        $years = array_map(fn($plan) => (int) new \DateTimeImmutable($plan['date'])->format('Y'), $budgetPlans);
        $minYear = !empty($years) ? min(min($years), $currentYear - 1) : $currentYear - 1;
        $maxYear = !empty($years) ? max(max($years), $currentYear) : $currentYear;
        $calendarStructure = array_combine(
            range($minYear, $maxYear + 1),
            array_map(fn() => array_fill(1, 12, null), range($minYear, $maxYear + 1))
        );
        array_walk($budgetPlans, function ($plan) use (&$calendarStructure) {
            $date = new \DateTimeImmutable($plan['date']);
            $calendarStructure[(int) $date->format('Y')][(int) $date->format('n')] = $plan['uuid'];
        });

        return $calendarStructure;
    }
}
