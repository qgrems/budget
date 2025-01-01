<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Infrastructure\Persistence\Repositories;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeView;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopesPaginated;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopesPaginatedInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class BudgetEnvelopeViewRepository implements BudgetEnvelopeViewRepositoryInterface
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
    public function save(BudgetEnvelopeViewInterface $budgetEnvelope): void
    {
        $this->connection->executeStatement('
    INSERT INTO budget_envelope_view (uuid, created_at, updated_at, current_budget, target_budget, name, user_uuid, is_deleted)
    VALUES (:uuid, :created_at, :updated_at, :current_budget, :target_budget, :name, :user_uuid, :is_deleted)
    ON DUPLICATE KEY UPDATE
        updated_at = VALUES(updated_at),
        current_budget = VALUES(current_budget),
        target_budget = VALUES(target_budget),
        name = VALUES(name),
        user_uuid = VALUES(user_uuid),
        is_deleted = VALUES(is_deleted)
', [
            'uuid' => $budgetEnvelope->getUuid(),
            'created_at' => $budgetEnvelope->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            'updated_at' => $budgetEnvelope->getUpdatedAt()->format(\DateTime::ATOM),
            'current_budget' => $budgetEnvelope->getCurrentBudget(),
            'target_budget' => $budgetEnvelope->getTargetBudget(),
            'name' => $budgetEnvelope->getName(),
            'user_uuid' => $budgetEnvelope->getUserUuid(),
            'is_deleted' => $budgetEnvelope->isDeleted() ? 1 : 0,
        ]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function delete(BudgetEnvelopeViewInterface $budgetEnvelope): void
    {
        $this->connection->delete('envelope', ['uuid' => $budgetEnvelope->getUuid()]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneBy(array $criteria, ?array $orderBy = null): ?BudgetEnvelopeViewInterface
    {
        $sql = sprintf('SELECT * FROM budget_envelope_view WHERE %s LIMIT 1', $this->buildWhereClause($criteria));
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAssociative();

        return $result ? BudgetEnvelopeView::createFromRepository($result) : null;
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneEnvelopeWithHistoryBy(array $criteria, ?array $orderBy = null): array
    {
        $sql = sprintf(
            'SELECT ev.uuid, ev.created_at, ev.updated_at, ev.current_budget, ev.target_budget, ev.name, ev.user_uuid, ev.is_deleted, ehv.aggregate_id, ehv.created_at AS history_created_at, ehv.monetary_amount, ehv.transaction_type
         FROM budget_envelope_view ev
         LEFT JOIN budget_envelope_history_view ehv ON ev.uuid = ehv.aggregate_id
         WHERE %s
         ORDER BY ehv.created_at',
            $this->buildWhereClauseWithAlias($criteria, 'ev')
        );
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAllAssociative();

        if (!$result) {
            return [];
        }

        $budgetEnvelopeData = $result[0];
        unset(
            $budgetEnvelopeData['aggregate_id'],
            $budgetEnvelopeData['monetary_amount'],
            $budgetEnvelopeData['transaction_type'],
            $budgetEnvelopeData['history_created_at']
        );
        $historyData = array_map(function ($row) {
            return [
                'aggregate_id' => $row['aggregate_id'],
                'created_at' => $row['history_created_at'],
                'monetary_amount' => $row['monetary_amount'],
                'transaction_type' => $row['transaction_type'],
            ];
        }, $result);

        return [
            'envelope' => $budgetEnvelopeData,
            'history' => $historyData,
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
        ?int $offset = null,
    ): BudgetEnvelopesPaginatedInterface {
        $sql = sprintf('SELECT * FROM budget_envelope_view WHERE %s', $this->buildWhereClause($criteria));

        if ($orderBy) {
            $sql = sprintf(
                '%s ORDER BY %s',
                $sql,
                implode(
                    ', ',
                    array_map(fn ($key, $value) => sprintf(
                        '%s %s',
                        $key,
                        $value,
                    ), array_keys($orderBy), $orderBy),
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

        return new BudgetEnvelopesPaginated(
            array_map([$this, 'mapToEnvelopeView'], $results),
            $count,
        );
    }

    private function buildWhereClause(array $criteria): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s IS NULL', $key) :
                sprintf('%s = :%s', $key, $key), array_keys($criteria), $criteria),
        );
    }

    private function buildWhereClauseWithAlias(array $criteria, string $alias): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s.%s IS NULL', $alias, $key) :
                sprintf('%s.%s = :%s', $alias, $key, $key), array_keys($criteria), $criteria),
        );
    }

    private function filterCriteria(array $criteria): array
    {
        return array_filter($criteria, fn ($value) => null !== $value);
    }

    private function mapToEnvelopeView(array $data): BudgetEnvelopeViewInterface
    {
        return BudgetEnvelopeView::createFromRepository($data);
    }
}
