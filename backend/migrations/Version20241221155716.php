<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241221155716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'This migrations removes the is_deleted column from the user_view table and renames the index uniq_8d93d649d17f50a6 to UNIQ_847CE747D17F50A6';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_view DROP is_deleted');
        $this->addSql('ALTER TABLE user_view RENAME INDEX uniq_8d93d649d17f50a6 TO UNIQ_847CE747D17F50A6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_view ADD is_deleted TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user_view RENAME INDEX uniq_847ce747d17f50a6 TO UNIQ_8D93D649D17F50A6');
    }
}
