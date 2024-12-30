<?php

declare(strict_types=1);

namespace App\UserManagement\Infrastructure\Persistence\Repositories;

use App\UserManagement\Domain\Ports\Inbound\UserViewInterface;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\ReadModels\Views\UserView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final class UserViewRepository implements UserViewRepositoryInterface
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
    public function save(UserViewInterface $user): void
    {
        $this->connection->executeStatement('
            INSERT INTO user_view (uuid, created_at, updated_at, email, password, firstname, lastname, consent_given, consent_date, roles, password_reset_token, password_reset_token_expiry)
            VALUES (:uuid, :created_at, :updated_at, :email, :password, :firstname, :lastname, :consent_given, :consent_date, :roles, :password_reset_token, :password_reset_token_expiry)
            ON DUPLICATE KEY UPDATE
                updated_at = VALUES(updated_at),
                email = VALUES(email),
                password = VALUES(password),
                firstname = VALUES(firstname),
                lastname = VALUES(lastname),
                consent_given = VALUES(consent_given),
                consent_date = VALUES(consent_date),
                roles = VALUES(roles),
                password_reset_token = VALUES(password_reset_token),
                password_reset_token_expiry = VALUES(password_reset_token_expiry)
        ', [
            'uuid' => $user->getUuid(),
            'created_at' => $user->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            'updated_at' => $user->getUpdatedAt()->format(\DateTime::ATOM),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'consent_given' => $user->isConsentGiven() ? 1 : 0,
            'consent_date' => $user->getConsentDate()?->format(\DateTimeImmutable::ATOM),
            'roles' => json_encode($user->getRoles()),
            'password_reset_token' => $user->getPasswordResetToken(),
            'password_reset_token_expiry' => $user->getPasswordResetTokenExpiry()?->format(\DateTimeImmutable::ATOM),
        ]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function delete(UserViewInterface $user): void
    {
        $this->connection->delete('user_view', ['uuid' => $user->getUuid()]);
    }

    /**
     * @throws Exception
     */
    #[\Override]
    public function findOneBy(array $criteria, ?array $orderBy = null): ?UserViewInterface
    {
        $sql = sprintf('SELECT * FROM user_view WHERE %s LIMIT 1', $this->buildWhereClause($criteria));
        $stmt = $this->connection->prepare($sql);
        $result = $stmt->executeQuery($criteria)->fetchAssociative();

        return $result ? UserView::createFromRepository($result) : null;
    }

    private function buildWhereClause(array $criteria): string
    {
        return implode(
            ' AND ',
            array_map(fn ($key, $value) => null === $value ? sprintf('%s IS NULL', $key) :
                sprintf('%s = :%s', $key, $key), array_keys($criteria), $criteria),
        );
    }
}
