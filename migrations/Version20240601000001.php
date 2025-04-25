<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240601000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create exchange_rate table for storing currency exchange rates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE exchange_rate (
            id SERIAL NOT NULL, 
            base_currency VARCHAR(3) NOT NULL, 
            target_currency VARCHAR(3) NOT NULL, 
            rate VARCHAR(255) NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_BASE_TARGET ON exchange_rate (base_currency, target_currency)');
        $this->addSql('COMMENT ON COLUMN exchange_rate.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN exchange_rate.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE exchange_rate');
    }
}
