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
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class BudgetPlanViewRepository implements BudgetPlanViewRepositoryInterface
{
    private Connection $connection;
    private const array BOOLEAN_FIELDS = ['is_deleted'];

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
        ON CONFLICT (uuid) DO UPDATE SET
            user_uuid = EXCLUDED.user_uuid,
            date = EXCLUDED.date,
            currency = EXCLUDED.currency,
            updated_at = EXCLUDED.updated_at,
            is_deleted = EXCLUDED.is_deleted
        ', [
            'uuid' => $budgetPlanView->uuid,
            'user_uuid' => $budgetPlanView->userId,
            'date' => $budgetPlanView->date->format(\DateTimeImmutable::ATOM),
            'currency' => $budgetPlanView->currency,
            'created_at' => $budgetPlanView->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetPlanView->updatedAt->format(\DateTime::ATOM),
            'is_deleted' => $budgetPlanView->isDeleted ? '1' : '0',
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
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('budget_plan_view')
            ->setMaxResults(1);

        $this->addWhereClauses($qb, $criteria);
        $this->addOrderByClauses($qb, $orderBy);

        $result = $qb->executeQuery()->fetchAssociative();

        return $result ? BudgetPlanView::fromRepository($result) : null;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOnePlanWithEntriesBy(array $criteria, ?array $orderBy = null): array
    {
        $sql = '
        SELECT pv.*,
        (SELECT json_agg(
            json_build_object(
                \'uuid\', nv.uuid,
                \'budget_plan_uuid\', nv.budget_plan_uuid,
                \'need_name\', nv.need_name,
                \'need_amount\', nv.need_amount,
                \'category\', nv.category,
                \'created_at\', nv.created_at,
                \'updated_at\', nv.updated_at
            )
        ) FROM budget_plan_need_entry_view nv WHERE pv.uuid = nv.budget_plan_uuid) AS needs,
        
        (SELECT json_agg(
            json_build_object(
                \'uuid\', sv.uuid,
                \'budget_plan_uuid\', sv.budget_plan_uuid,
                \'saving_name\', sv.saving_name,
                \'saving_amount\', sv.saving_amount,
                \'category\', sv.category,
                \'created_at\', sv.created_at,
                \'updated_at\', sv.updated_at
            )
        ) FROM budget_plan_saving_entry_view sv WHERE pv.uuid = sv.budget_plan_uuid) AS savings,
        
        (SELECT json_agg(
            json_build_object(
                \'uuid\', wv.uuid,
                \'budget_plan_uuid\', wv.budget_plan_uuid,
                \'want_name\', wv.want_name,
                \'want_amount\', wv.want_amount,
                \'category\', wv.category,
                \'created_at\', wv.created_at,
                \'updated_at\', wv.updated_at
            )
        ) FROM budget_plan_want_entry_view wv WHERE pv.uuid = wv.budget_plan_uuid) AS wants,
        
        (SELECT json_agg(
            json_build_object(
                \'uuid\', iv.uuid,
                \'budget_plan_uuid\', iv.budget_plan_uuid,
                \'income_name\', iv.income_name,
                \'income_amount\', iv.income_amount,
                \'category\', iv.category,
                \'created_at\', iv.created_at,
                \'updated_at\', iv.updated_at
            )
        ) FROM budget_plan_income_entry_view iv WHERE pv.uuid = iv.budget_plan_uuid) AS incomes
        
        FROM budget_plan_view pv
        WHERE ';

        $sql .= $this->buildWhereClauseWithAlias($criteria, 'pv');

        if ($orderBy) {
            $orderByClauses = [];
            foreach ($orderBy as $field => $direction) {
                $orderByClauses[] = "{$field} {$direction}";
            }
            $sql .= ' ORDER BY ' . implode(', ', $orderByClauses);
        }

        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($this->processCriteria($criteria))->fetchAssociative();

        if (!$result) {
            return [];
        }

        $incomes = json_decode($result['incomes'] ?? '[]', true);
        $needs = json_decode($result['needs'] ?? '[]', true);
        $savings = json_decode($result['savings'] ?? '[]', true);
        $wants = json_decode($result['wants'] ?? '[]', true);

        $incomeCategories = $this->calculateCategoryData($incomes, 'income_amount');
        $needCategories = $this->calculateCategoryData($needs, 'need_amount');
        $savingCategories = $this->calculateCategoryData($savings, 'saving_amount');
        $wantCategories = $this->calculateCategoryData($wants, 'want_amount');

        return [
            'budgetPlan' => BudgetPlanView::fromRepository($result),
            'needs' => array_map([$this, 'mapToBudgetPlanNeedEntryView'], $needs ?: []),
            'savings' => array_map([$this, 'mapToBudgetPlanSavingEntryView'], $savings ?: []),
            'wants' => array_map([$this, 'mapToBudgetPlanWantEntryView'], $wants ?: []),
            'incomes' => array_map([$this, 'mapToBudgetPlanIncomeEntryView'], $incomes ?: []),
            'incomeCategoriesRatio' => $incomeCategories['ratios'],
            'incomesTotal' => $incomeCategories['totals'],
            'needCategoriesRatio' => $needCategories['ratios'],
            'needsTotal' => $needCategories['totals'],
            'savingCategoriesRatio' => $savingCategories['ratios'],
            'savingsTotal' => $savingCategories['totals'],
            'wantCategoriesRatio' => $wantCategories['ratios'],
            'wantsTotal' => $wantCategories['totals'],
        ];
    }

    private function calculateCategoryData(array $entries, string $amountField): array
    {
        $totals = [];
        $ratios = [];

        foreach ($entries as $entry) {
            $category = $entry['category'] ?? 'Uncategorized';
            $amount = (float)$entry[$amountField];

            if (!isset($totals[$category])) {
                $totals[$category] = 0;
            }
            $totals[$category] += $amount;
        }

        $totalAmount = array_sum($totals);
        if ($totalAmount > 0) {
            foreach ($totals as $category => $amount) {
                $ratios[$category] = $this->formatPercentage($amount / $totalAmount);
            }
        }

        return [
            'totals' => $totals,
            'ratios' => $ratios
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
    public function getACalendarWithItsBudgetPlansFinancialRatiosByYear(
        array $criteria,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $sql = sprintf(
            'SELECT
        EXTRACT(YEAR FROM pv.date) AS year,
        EXTRACT(MONTH FROM pv.date) AS month,
        pv.uuid,
        json_build_object(
            \'incomeCategories\', jsonb_object_agg(iv.category, CAST(iv.income_amount AS NUMERIC)),
            \'needCategories\', jsonb_object_agg(nv.category, CAST(nv.need_amount AS NUMERIC)),
            \'savingCategories\', jsonb_object_agg(sv.category, CAST(sv.saving_amount AS NUMERIC)),
            \'wantCategories\', jsonb_object_agg(wv.category, CAST(wv.want_amount AS NUMERIC))
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
        } else {
            $sql .= ' ORDER BY year DESC, month ASC';
        }

        if ($limit) {
            $sql = sprintf('%s LIMIT %d', $sql, $limit);
        }

        if ($offset) {
            $sql = sprintf('%s OFFSET %d', $sql, $offset);
        }

        $results = $this->connection->prepare($sql)->executeQuery($this->processCriteria($criteria))->fetchAllAssociative();

        $formattedResults = [];
        $yearlyTotals = [
            'incomeCategories' => [],
            'needCategories' => [],
            'savingCategories' => [],
            'wantCategories' => [],
        ];

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
                if (!$categories) return;

                array_walk($categories, function ($amount, $category) use (&$totals) {
                    if (!isset($totals[$category])) {
                        $totals[$category] = 0;
                    }
                    $totals[$category] += (float) $amount;
                });
            };

            foreach ($categories as $categoryType => $categoryAmounts) {
                if (isset($yearlyTotals[$categoryType])) {
                    $updateTotals($yearlyTotals[$categoryType], $categoryAmounts);
                }
            }
        });

        array_walk($yearlyTotals, function ($totals, $key) use (&$formattedResults) {
            $totalAmount = array_sum($totals);
            $ratios = [];

            if ($totalAmount > 0) {
                foreach ($totals as $category => $amount) {
                    $ratios[$category] = $amount / $totalAmount;
                }
            }

            $formattedResults[$key . 'Ratio'] = array_map([$this, 'formatPercentage'], $ratios);
            $formattedResults[$key . 'Total'] = array_map(fn($amount) => (string) round($amount, 2), $totals);
        });

        return $formattedResults;
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
        $qb = $this->connection->createQueryBuilder()
            ->select('uuid', 'date')
            ->from('budget_plan_view');

        $this->addWhereClauses($qb, $criteria);
        $this->addOrderByClauses($qb, $orderBy);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function addWhereClauses(QueryBuilder $qb, array $criteria): void
    {
        $processedCriteria = $this->processCriteria($criteria);

        foreach ($processedCriteria as $field => $value) {
            if ($value === null) {
                $qb->andWhere($qb->expr()->isNull($field));
            } else if ($field === 'year') {
                $qb->andWhere('EXTRACT(YEAR FROM date) = :year');
                $qb->setParameter('year', $value);
            } else {
                $qb->andWhere($qb->expr()->eq($field, ":$field"));
                $qb->setParameter($field, $value);
            }
        }
    }

    private function addOrderByClauses(QueryBuilder $qb, ?array $orderBy): void
    {
        if (!$orderBy) {
            return;
        }

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy($field, $direction);
        }
    }

    private function buildWhereClauseWithAlias(array $criteria, string $alias): string
    {
        $processed = $this->processCriteria($criteria);
        $clauses = [];

        foreach ($processed as $field => $value) {
            if ($value === null) {
                $clauses[] = "{$alias}.{$field} IS NULL";
            } else if ($field === 'year') {
                $clauses[] = "EXTRACT(YEAR FROM {$alias}.date) = :{$field}";
            } else {
                $clauses[] = "{$alias}.{$field} = :{$field}";
            }
        }

        return !empty($clauses) ? implode(' AND ', $clauses) : '1=1';
    }

    private function processCriteria(array $criteria): array
    {
        $processed = [];

        foreach ($criteria as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (in_array($field, self::BOOLEAN_FIELDS, true)) {
                $processed[$field] = is_bool($value) ? ($value ? '1' : '0') : '0';
            } else {
                $processed[$field] = $value;
            }
        }

        if (!isset($processed['is_deleted']) && !isset($criteria['is_deleted'])) {
            $processed['is_deleted'] = '0';
        }

        return $processed;
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
