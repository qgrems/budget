<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlansPaginatedInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanNeedEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlansPaginated;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanWantEntryView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class BudgetPlanViewRepository implements BudgetPlanViewRepositoryInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function save(BudgetPlanViewInterface $budgetPlanView): void
    {
        $this->connection->executeStatement('
            INSERT INTO budget_plan_view (uuid, user_uuid, date, created_at, updated_at, is_deleted)
            VALUES (:uuid, :user_uuid, :date, :created_at, :updated_at, :is_deleted)
            ON DUPLICATE KEY UPDATE
                user_uuid = VALUES(user_uuid),
                date = VALUES(date),
                updated_at = VALUES(updated_at),
                is_deleted = VALUES(is_deleted)
        ', [
            'uuid' => $budgetPlanView->uuid,
            'user_uuid' => $budgetPlanView->userId,
            'date' => $budgetPlanView->date->format(\DateTimeImmutable::ATOM),
            'created_at' => $budgetPlanView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanView->updatedAt->format(\DateTime::ATOM),
            'is_deleted' => $budgetPlanView->isDeleted ? 1 : 0,
        ]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function delete(BudgetPlanViewInterface $budgetPlanView): void
    {
        $this->connection->delete('budget_plan_view', ['uuid' => $budgetPlanView->uuid]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetPlanViewInterface
    {
        $sql = sprintf('SELECT * FROM budget_plan_view WHERE %s LIMIT 1', $this->buildWhereClause($criteria));
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAssociative();

        return $result ? BudgetPlanView::fromRepository($result) : null;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOnePlanWithEntriesBy(array $criteria, ?array $orderBy = null): array
    {
        $sql = sprintf(
            'SELECT pv.*,
                (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    "uuid", nv.uuid,
                    "budget_plan_uuid", nv.budget_plan_uuid,
                    "need_name", nv.need_name,
                    "need_amount", nv.need_amount,
                    "created_at", nv.created_at,
                    "updated_at", nv.updated_at
                )) FROM budget_plan_need_entry_view nv WHERE pv.uuid = nv.budget_plan_uuid) AS needs,
                (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    "uuid", sv.uuid,
                    "budget_plan_uuid", sv.budget_plan_uuid,
                    "saving_name", sv.saving_name,
                    "saving_amount", sv.saving_amount,
                    "created_at", sv.created_at,
                    "updated_at", sv.updated_at
                )) FROM budget_plan_saving_entry_view sv WHERE pv.uuid = sv.budget_plan_uuid) AS savings,
                (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    "uuid", wv.uuid,
                    "budget_plan_uuid", wv.budget_plan_uuid,
                    "want_name", wv.want_name,
                    "want_amount", wv.want_amount,
                    "created_at", wv.created_at,
                    "updated_at", wv.updated_at
                )) FROM budget_plan_want_entry_view wv WHERE pv.uuid = wv.budget_plan_uuid) AS wants,
                (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                    "uuid", iv.uuid,
                    "budget_plan_uuid", iv.budget_plan_uuid,
                    "income_name", iv.income_name,
                    "income_amount", iv.income_amount,
                    "created_at", iv.created_at,
                    "updated_at", iv.updated_at
                )) FROM budget_plan_income_entry_view iv WHERE pv.uuid = iv.budget_plan_uuid) AS incomes
         FROM budget_plan_view pv
         WHERE %s
         GROUP BY pv.uuid',
            $this->buildWhereClauseWithAlias($criteria, 'pv')
        );
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAssociative();

        if (!$result) {
            return [];
        }

        return [
            'budgetPlan' => BudgetPlanView::fromRepository($result),
            'needs' => array_map([$this, 'mapToBudgetPlanNeedEntryView'], json_decode($result['needs'], true)),
            'savings' => array_map([$this, 'mapToBudgetPlanSavingEntryView'], json_decode($result['savings'], true)),
            'wants' => array_map([$this, 'mapToBudgetPlanWantEntryView'], json_decode($result['wants'], true)),
            'incomes' => array_map([$this, 'mapToBudgetPlanIncomeEntryView'], json_decode($result['incomes'], true)),
        ];
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findBy(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): BudgetPlansPaginatedInterface {
        $sql = sprintf('SELECT * FROM budget_plan_view WHERE %s', $this->buildWhereClause($criteria));

        if ($orderBy) {
            $sql = sprintf(
                '%s ORDER BY %s',
                $sql,
                implode(
                    ', ',
                    array_map(fn ($key, $value) => sprintf('%s %s', $key, $value), array_keys($orderBy), $orderBy)
                )
            );
        }

        if ($limit) {
            $sql = sprintf('%s LIMIT %d', $sql, $limit);
        }

        if ($offset) {
            $sql = sprintf('%s OFFSET %d', $sql, $offset);
        }

        $stmt = $this->connection->prepare($sql);
        $query = $stmt->executeQuery($this->filterCriteria($criteria));
        $results = $query->fetchAllAssociative();
        $count = $query->rowCount();

        return new BudgetPlansPaginated(
            array_map([$this, 'mapToBudgetPlanView'], $results),
            $count
        );
    }

    private function buildWhereClause(array $criteria): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s IS NULL', $key) : sprintf('%s = :%s', $key, $key), array_keys($criteria), $criteria)
        );
    }

    private function buildWhereClauseWithAlias(array $criteria, string $alias): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s.%s IS NULL', $alias, $key) : sprintf('%s.%s = :%s', $alias, $key, $key), array_keys($criteria), $criteria)
        );
    }

    private function filterCriteria(array $criteria): array
    {
        return array_filter($criteria, fn ($value) => null !== $value);
    }

    private function mapToBudgetPlanView(array $data): BudgetPlanViewInterface
    {
        return BudgetPlanView::fromRepository($data);
    }

    private function mapToBudgetPlanNeedEntryView(array $data): BudgetPlanNeedEntryView
    {
        return BudgetPlanNeedEntryView::fromRepository($data);
    }

    private function mapToBudgetPlanWantEntryView(array $data): BudgetPlanWantEntryView
    {
        return BudgetPlanWantEntryView::fromRepository($data);
    }

    private function mapToBudgetPlanSavingEntryView(array $data): BudgetPlanSavingEntryView
    {
        return BudgetPlanSavingEntryView::fromRepository($data);
    }

    private function mapToBudgetPlanIncomeEntryView(array $data): BudgetPlanIncomeEntryView
    {
        return BudgetPlanIncomeEntryView::fromRepository($data);
    }
}
