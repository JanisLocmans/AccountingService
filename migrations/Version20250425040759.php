<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250425040759 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX idx_base_target
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_723705d1ad5cdbf3 RENAME TO IDX_723705D1E7DF2E9E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_723705d1bef91dd6 RENAME TO IDX_723705D1C652C408
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_723705d1c652c408 RENAME TO idx_723705d1bef91dd6
        SQL);
        $this->addSql(<<<'SQL'
            ALTER INDEX idx_723705d1e7df2e9e RENAME TO idx_723705d1ad5cdbf3
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX idx_base_target ON exchange_rate (base_currency, target_currency)
        SQL);
    }
}
