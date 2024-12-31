<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241230235412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This migration creates the envelope_history_view table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE envelope_history_view (id INT AUTO_INCREMENT NOT NULL, aggregate_id VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, monetary_amount VARCHAR(13) NOT NULL, transaction_type VARCHAR(6) NOT NULL, user_uuid VARCHAR(36) NOT NULL, INDEX idx_envelope_history_view_user_uuid (user_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE envelope_history_view');
    }
}
