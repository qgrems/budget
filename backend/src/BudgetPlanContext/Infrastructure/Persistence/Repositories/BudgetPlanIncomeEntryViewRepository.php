<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use Doctrine\DBAL\Connection;

final class BudgetPlanIncomeEntryViewRepository implements BudgetPlanIncomeEntryViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findOneByUuid(string $uuid): ?BudgetPlanIncomeEntryViewInterface
    {
        $sql = 'SELECT uuid, budget_plan_uuid, income_name, income_amount, category, created_at, updated_at
            FROM budget_plan_income_entry_view
            WHERE uuid = :uuid';

        $result = $this->connection->fetchAssociative($sql, ['uuid' => $uuid]);

        if ($result === false) {
            return null;
        }

        return BudgetPlanIncomeEntryView::fromRepository($result);
    }

    #[\Override]
    public function save(BudgetPlanIncomeEntryViewInterface $budgetPlanIncomeEntryView): void
    {
        $this->connection->executeStatement('
            INSERT INTO budget_plan_income_entry_view (uuid, budget_plan_uuid, income_name, income_amount, category, created_at, updated_at)
            VALUES (:uuid, :budget_plan_uuid, :income_name, :income_amount, :category, :created_at, :updated_at)
            ON DUPLICATE KEY UPDATE
                income_name = VALUES(income_name),
                income_amount = VALUES(income_amount),
                updated_at = VALUES(updated_at),
                category = VALUES(category)
        ', [
            'uuid' => $budgetPlanIncomeEntryView->uuid,
            'budget_plan_uuid' => $budgetPlanIncomeEntryView->budgetPlanUuid,
            'income_name' => $budgetPlanIncomeEntryView->incomeName,
            'income_amount' => $budgetPlanIncomeEntryView->incomeAmount,
            'category' => $budgetPlanIncomeEntryView->category,
            'created_at' => $budgetPlanIncomeEntryView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanIncomeEntryView->updatedAt->format(\DateTime::ATOM),
        ]);
    }

    #[\Override]
    public function delete(string $uuid): void
    {
        $this->connection->delete('budget_plan_income_entry_view', ['uuid' => $uuid]);
    }
}
