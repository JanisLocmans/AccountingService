<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240601000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema';
    }

    public function up(Schema $schema): void
    {
        // Create client table
        $this->addSql('CREATE TABLE client (
            id SERIAL NOT NULL, 
            name VARCHAR(255) NOT NULL, 
            email VARCHAR(255) NOT NULL, 
            PRIMARY KEY(id)
        )');
        
        // Create account table
        $this->addSql('CREATE TABLE account (
            id SERIAL NOT NULL, 
            client_id INT NOT NULL, 
            account_number VARCHAR(255) NOT NULL, 
            currency VARCHAR(3) NOT NULL, 
            balance DOUBLE PRECISION NOT NULL, 
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_7D3656A419EB6921 ON account (client_id)');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A419EB6921 FOREIGN KEY (client_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        
        // Create transaction table
        $this->addSql('CREATE TABLE transaction (
            id SERIAL NOT NULL, 
            source_account_id INT NOT NULL, 
            destination_account_id INT NOT NULL, 
            amount DOUBLE PRECISION NOT NULL, 
            currency VARCHAR(3) NOT NULL, 
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
            description VARCHAR(255) DEFAULT NULL, 
            exchange_rate DOUBLE PRECISION NOT NULL, 
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_723705D1AD5CDBF3 ON transaction (source_account_id)');
        $this->addSql('CREATE INDEX IDX_723705D1BEF91DD6 ON transaction (destination_account_id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1AD5CDBF3 FOREIGN KEY (source_account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1BEF91DD6 FOREIGN KEY (destination_account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        
        // Add created_at column to transaction table
        $this->addSql('COMMENT ON COLUMN transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1AD5CDBF3');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1BEF91DD6');
        $this->addSql('ALTER TABLE account DROP CONSTRAINT FK_7D3656A419EB6921');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE client');
    }
}
