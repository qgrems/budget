<?php

declare(strict_types=1);

namespace App\UserContext\Infrastructure\Persistence\Repositories;

use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\ReadModels\Views\UserView;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class UserViewRepository implements UserViewRepositoryInterface
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
            INSERT INTO user_view (uuid, created_at, updated_at, email, password, firstname, lastname, language_preference, consent_given, consent_date, roles, password_reset_token, password_reset_token_expiry)
            VALUES (:uuid, :created_at, :updated_at, :email, :password, :firstname, :lastname, :language_preference, :consent_given, :consent_date, :roles, :password_reset_token, :password_reset_token_expiry)
            ON DUPLICATE KEY UPDATE
                updated_at = VALUES(updated_at),
                email = VALUES(email),
                password = VALUES(password),
                firstname = VALUES(firstname),
                lastname = VALUES(lastname),
                language_preference = VALUES(language_preference),
                consent_given = VALUES(consent_given),
                consent_date = VALUES(consent_date),
                roles = VALUES(roles),
                password_reset_token = VALUES(password_reset_token),
                password_reset_token_expiry = VALUES(password_reset_token_expiry)
        ', [
            'uuid' => $user->uuid,
            'created_at' => $user->createdAt->format(\DateTimeImmutable::ATOM),
            'updated_at' => $user->updatedAt->format(\DateTime::ATOM),
            'email' => $user->email,
            'password' => $user->password,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'language_preference' => $user->languagePreference,
            'consent_given' => $user->consentGiven ? 1 : 0,
            'consent_date' => $user->consentDate->format(\DateTimeImmutable::ATOM),
            'roles' => json_encode($user->roles),
            'password_reset_token' => $user->passwordResetToken,
            'password_reset_token_expiry' => $user->passwordResetTokenExpiry?->format(\DateTimeImmutable::ATOM),
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

        return $result ? UserView::fromRepository($result) : null;
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
