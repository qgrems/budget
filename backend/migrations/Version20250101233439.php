<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101233439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This migration creates the tables for the budget envelope and user views, the event store, and the refresh tokens.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE budget_envelope_history_view (id INT AUTO_INCREMENT NOT NULL, aggregate_id VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, monetary_amount VARCHAR(13) NOT NULL, transaction_type VARCHAR(6) NOT NULL, user_uuid VARCHAR(36) NOT NULL, INDEX idx_budget_envelope_history_view_aggregate_id (aggregate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE budget_envelope_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, current_amount VARCHAR(13) NOT NULL, targeted_amount VARCHAR(13) NOT NULL, name VARCHAR(50) NOT NULL, user_uuid VARCHAR(36) NOT NULL, is_deleted TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_3C39B684D17F50A6 (uuid), INDEX idx_budget_envelope_view_user_uuid (user_uuid), INDEX idx_budget_envelope_view_uuid (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_store (id INT AUTO_INCREMENT NOT NULL, aggregate_id VARCHAR(36) NOT NULL, type VARCHAR(255) NOT NULL, payload JSON NOT NULL, occurred_on DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX idx_aggregate_id (aggregate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(128) NOT NULL, username VARCHAR(255) NOT NULL, valid DATETIME NOT NULL, UNIQUE INDEX UNIQ_9BACE7E1C74F2195 (refresh_token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_view (id INT AUTO_INCREMENT NOT NULL, uuid VARCHAR(36) NOT NULL, email VARCHAR(320) NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(50) NOT NULL, lastname VARCHAR(50) NOT NULL, consent_given TINYINT(1) NOT NULL, consent_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL, roles JSON NOT NULL, password_reset_token VARCHAR(64) DEFAULT NULL, password_reset_token_expiry DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_847CE747D17F50A6 (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE budget_envelope_history_view');
        $this->addSql('DROP TABLE budget_envelope_view');
        $this->addSql('DROP TABLE event_store');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE user_view');
    }
}
