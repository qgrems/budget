<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Persistence\Repositories;

use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanNeedEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
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
            INSERT INTO budget_plan_view (uuid, user_uuid, date, currency, created_at, updated_at, is_deleted)
            VALUES (:uuid, :user_uuid, :date, :currency, :created_at, :updated_at, :is_deleted)
            ON DUPLICATE KEY UPDATE
                user_uuid = VALUES(user_uuid),
                date = VALUES(date),
                currency = VALUES(currency),
                updated_at = VALUES(updated_at),
                is_deleted = VALUES(is_deleted)
        ', [
            'uuid' => $budgetPlanView->uuid,
            'user_uuid' => $budgetPlanView->userId,
            'date' => $budgetPlanView->date->format(\DateTimeImmutable::ATOM),
            'currency' => $budgetPlanView->currency,
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
                "category", nv.category,
                "created_at", nv.created_at,
                "updated_at", nv.updated_at
            )) FROM budget_plan_need_entry_view nv WHERE pv.uuid = nv.budget_plan_uuid) AS needs,
            (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                "uuid", sv.uuid,
                "budget_plan_uuid", sv.budget_plan_uuid,
                "saving_name", sv.saving_name,
                "saving_amount", sv.saving_amount,
                "category", sv.category,
                "created_at", sv.created_at,
                "updated_at", sv.updated_at
            )) FROM budget_plan_saving_entry_view sv WHERE pv.uuid = sv.budget_plan_uuid) AS savings,
            (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                "uuid", wv.uuid,
                "budget_plan_uuid", wv.budget_plan_uuid,
                "want_name", wv.want_name,
                "want_amount", wv.want_amount,
                "category", wv.category,
                "created_at", wv.created_at,
                "updated_at", wv.updated_at
            )) FROM budget_plan_want_entry_view wv WHERE pv.uuid = wv.budget_plan_uuid) AS wants,
            (SELECT JSON_ARRAYAGG(JSON_OBJECT(
                "uuid", iv.uuid,
                "budget_plan_uuid", iv.budget_plan_uuid,
                "income_name", iv.income_name,
                "income_amount", iv.income_amount,
                "category", iv.category,
                "created_at", iv.created_at,
                "updated_at", iv.updated_at
            )) FROM budget_plan_income_entry_view iv WHERE pv.uuid = iv.budget_plan_uuid) AS incomes,
            JSON_OBJECT(
                "incomeCategoriesRatio", JSON_OBJECTAGG(iv.category, iv.income_amount / iv.total_income_amount),
                "incomesTotal", JSON_OBJECTAGG(iv.category, iv.income_amount)
            ) AS incomeCategories,
            JSON_OBJECT(
                "needCategoriesRatio", JSON_OBJECTAGG(nv.category, nv.need_amount / nv.total_need_amount),
                "needsTotal", JSON_OBJECTAGG(nv.category, nv.need_amount)
            ) AS needCategories,
            JSON_OBJECT(
                "savingCategoriesRatio", JSON_OBJECTAGG(sv.category, sv.saving_amount / sv.total_saving_amount),
                "savingsTotal", JSON_OBJECTAGG(sv.category, sv.saving_amount)
            ) AS savingCategories,
            JSON_OBJECT(
                "wantCategoriesRatio", JSON_OBJECTAGG(wv.category, wv.want_amount / wv.total_want_amount),
                "wantsTotal", JSON_OBJECTAGG(wv.category, wv.want_amount)
            ) AS wantCategories
        FROM budget_plan_view pv
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                income_amount,
                SUM(income_amount) OVER (PARTITION BY budget_plan_uuid) AS total_income_amount
            FROM budget_plan_income_entry_view
        ) iv ON pv.uuid = iv.budget_plan_uuid
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                need_amount,
                SUM(need_amount) OVER (PARTITION BY budget_plan_uuid) AS total_need_amount
            FROM budget_plan_need_entry_view
        ) nv ON pv.uuid = nv.budget_plan_uuid
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                saving_amount,
                SUM(saving_amount) OVER (PARTITION BY budget_plan_uuid) AS total_saving_amount
            FROM budget_plan_saving_entry_view
        ) sv ON pv.uuid = sv.budget_plan_uuid
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                want_amount,
                SUM(want_amount) OVER (PARTITION BY budget_plan_uuid) AS total_want_amount
            FROM budget_plan_want_entry_view
        ) wv ON pv.uuid = wv.budget_plan_uuid
        WHERE %s
        GROUP BY pv.uuid',
            $this->buildWhereClauseWithAlias($criteria, 'pv')
        );

        if ($orderBy) {
            $sql = sprintf(
                '%s ORDER BY %s',
                $sql,
                implode(
                    ', ',
                    array_map(fn($key, $value) => sprintf('%s %s', $key, $value), array_keys($orderBy), $orderBy)
                )
            );
        }

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAssociative();

        if (!$result) {
            return [];
        }

        $incomes = json_decode($result['incomes'], true);
        $needs = json_decode($result['needs'], true);
        $savings = json_decode($result['savings'], true);
        $wants = json_decode($result['wants'], true);
        $incomeCategories = json_decode($result['incomeCategories'], true);
        $needCategories = json_decode($result['needCategories'], true);
        $savingCategories = json_decode($result['savingCategories'], true);
        $wantCategories = json_decode($result['wantCategories'], true);

        return [
            'budgetPlan' => BudgetPlanView::fromRepository($result),
            'needs' => array_map([$this, 'mapToBudgetPlanNeedEntryView'], $needs ?? []),
            'savings' => array_map([$this, 'mapToBudgetPlanSavingEntryView'], $savings ?? []),
            'wants' => array_map([$this, 'mapToBudgetPlanWantEntryView'], $wants ?? []),
            'incomes' => array_map([$this, 'mapToBudgetPlanIncomeEntryView'], $incomes ?? []),
            'incomeCategoriesRatio' => array_map([$this, 'formatPercentage'], $incomeCategories['incomeCategoriesRatio']),
            'incomesTotal' => $incomeCategories['incomesTotal'],
            'needCategoriesRatio' => array_map([$this, 'formatPercentage'], $needCategories['needCategoriesRatio']),
            'needsTotal' => $needCategories['needsTotal'],
            'savingCategoriesRatio' => array_map([$this, 'formatPercentage'], $savingCategories['savingCategoriesRatio']),
            'savingsTotal' => $savingCategories['savingsTotal'],
            'wantCategoriesRatio' => array_map([$this, 'formatPercentage'], $wantCategories['wantCategoriesRatio']),
            'wantsTotal' => $wantCategories['wantsTotal'],
        ];
    }

    private function formatPercentage(float $value): string
    {
        return round($value * 100) . ' %';
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
    ): array {
        $sql = sprintf(
            'SELECT
            YEAR(pv.date) AS year,
            MONTH(pv.date) AS month,
            pv.uuid,
            JSON_OBJECT(
                "incomeCategories", JSON_OBJECTAGG(iv.category, iv.income_amount),
                "needCategories", JSON_OBJECTAGG(nv.category, nv.need_amount),
                "savingCategories", JSON_OBJECTAGG(sv.category, sv.saving_amount),
                "wantCategories", JSON_OBJECTAGG(wv.category, wv.want_amount)
            ) AS categories
        FROM budget_plan_view pv
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                income_amount
            FROM budget_plan_income_entry_view
        ) iv ON pv.uuid = iv.budget_plan_uuid
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                need_amount
            FROM budget_plan_need_entry_view
        ) nv ON pv.uuid = nv.budget_plan_uuid
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                saving_amount
            FROM budget_plan_saving_entry_view
        ) sv ON pv.uuid = sv.budget_plan_uuid
        LEFT JOIN (
            SELECT
                budget_plan_uuid,
                category,
                want_amount
            FROM budget_plan_want_entry_view
        ) wv ON pv.uuid = wv.budget_plan_uuid
        WHERE %s
        GROUP BY year, month, pv.uuid',
            $this->buildWhereClauseWithYear($criteria)
        );

        if ($orderBy) {
            $sql = sprintf(
                '%s ORDER BY %s',
                $sql,
                implode(
                    ', ',
                    array_map(fn($key, $value) => sprintf('%s %s', $key, $value), array_keys($orderBy), $orderBy)
                )
            );
        }

        if ($limit) {
            $sql = sprintf('%s LIMIT %d', $sql, $limit);
        }

        if ($offset) {
            $sql = sprintf('%s OFFSET %d', $sql, $offset);
        }

        $results = $this->connection->prepare($sql)->executeQuery($this->filterCriteria($criteria))->fetchAllAssociative();

        $formattedResults = [];
        $yearlyTotals = [
            'incomeCategories' => [],
            'needCategories' => [],
            'savingCategories' => [],
            'wantCategories' => [],
        ];

        // Initialize the formattedResults array with the required structure
        $year = $criteria['year'] ?? date('Y');
        $formattedResults[$year] = array_fill(1, 12, ['uuid' => null]);

        array_walk($results, function ($result) use (&$formattedResults, &$yearlyTotals) {
            $year = (int)$result['year'];
            $month = (int)$result['month'];

            $formattedResults[$year][$month] = [
                'uuid' => $result['uuid'],
            ];

            $categories = json_decode($result['categories'], true);

            $updateTotals = function (&$totals, $categories) {
                array_walk($categories, function ($amount, $category) use (&$totals) {
                    if (!isset($totals[$category])) {
                        $totals[$category] = 0;
                    }
                    $totals[$category] += $amount;
                });
            };

            array_walk($categories, function ($categoryAmounts, $categoryType) use (&$yearlyTotals, $updateTotals) {
                $updateTotals($yearlyTotals[$categoryType], $categoryAmounts);
            });
        });

        array_walk($yearlyTotals, function (&$totals, $key) use (&$formattedResults) {
            $totalAmount = array_sum($totals);
            $ratios = array_map(fn($amount) => $amount / $totalAmount, $totals);
            $formattedResults[$key . 'Ratio'] = array_map([$this, 'formatPercentage'], $ratios);
            $formattedResults[$key . 'Total'] = array_map(fn($amount) => (string) round($amount, 2), $totals);
        });

        return $formattedResults;
    }

    private function buildWhereClauseWithYear(array $criteria): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => $key === 'year' ? sprintf('YEAR(date) = :%s', $key) : (null === $value ? sprintf('%s IS NULL', $key) : sprintf('%s = :%s', $key, $key)), array_keys($criteria), $criteria)
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
