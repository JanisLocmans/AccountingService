<?php

namespace App\Tests\Entity;

use App\Entity\Account;
use App\Entity\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $this->client = new Client();
    }

    public function testGettersAndSetters(): void
    {
        $this->client->setId(1);
        $this->client->setName('Test Client');
        $this->client->setEmail('test@example.com');

        $this->assertEquals(1, $this->client->getId());
        $this->assertEquals('Test Client', $this->client->getName());
        $this->assertEquals('test@example.com', $this->client->getEmail());
    }

    public function testAddAccount(): void
    {
        $account = new Account();
        $account->setAccountNumber('ACC001');

        $this->client->addAccount($account);

        $this->assertCount(1, $this->client->getAccounts());
        $this->assertSame($this->client, $account->getClient());
    }

    public function testRemoveAccount(): void
    {
        $account = new Account();
        $account->setAccountNumber('ACC001');

        $this->client->addAccount($account);
        $this->assertCount(1, $this->client->getAccounts());

        $this->client->removeAccount($account);
        $this->assertCount(0, $this->client->getAccounts());
    }
}
