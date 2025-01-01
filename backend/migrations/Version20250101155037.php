<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101155037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This migration updates the user_view table to change the length of the uuid, email, firstname, and lastname columns.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_view CHANGE uuid uuid VARCHAR(36) NOT NULL, CHANGE email email VARCHAR(320) NOT NULL, CHANGE firstname firstname VARCHAR(50) NOT NULL, CHANGE lastname lastname VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_view CHANGE uuid uuid VARCHAR(100) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE firstname firstname VARCHAR(255) NOT NULL, CHANGE lastname lastname VARCHAR(255) NOT NULL');
    }
}
