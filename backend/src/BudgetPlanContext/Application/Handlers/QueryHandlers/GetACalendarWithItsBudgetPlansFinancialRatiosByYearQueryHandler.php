<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\QueryHandlers;

use App\BudgetPlanContext\Application\Queries\GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;

final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearQueryHandler
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
    ) {
    }

    public function __invoke(GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery $getACalendarWithItsBudgetPlansFinancialRatiosByYearQuery): array
    {
        return $this->budgetPlanViewRepository->findBy(
            [
                'user_uuid' => (string) $getACalendarWithItsBudgetPlansFinancialRatiosByYearQuery->getBudgetPlanUserId(),
                'year' => $getACalendarWithItsBudgetPlansFinancialRatiosByYearQuery->getDate()->format('Y'),
                'is_deleted' => false,
            ],
        );
    }

    /**private function generateCalendarFromBudgetPlans(array $budgetPlans): array
    {
        return array_reduce($budgetPlans, function (array $calendarStructure, array $plan) {
            $date = new \DateTimeImmutable($plan['date']);
            $year = (int)$date->format('Y');
            $month = (int)$date->format('n');

            if (!isset($calendarStructure[$year])) {
                $calendarStructure[$year] = [];
            }

            $calendarStructure[$year][$month] = $plan['uuid'];

            return $calendarStructure;
        }, []);
    }**/
}
