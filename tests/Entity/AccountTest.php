<?php

namespace App\Tests\Entity;

use App\Entity\Account;
use App\Entity\Client;
use App\Entity\Transaction;
use PHPUnit\Framework\TestCase;

class AccountTest extends TestCase
{
    private Account $account;
    
    protected function setUp(): void
    {
        $this->account = new Account();
    }
    
    public function testGettersAndSetters(): void
    {
        $client = new Client();
        $client->setName('Test Client');
        
        $this->account->setId(1);
        $this->account->setClient($client);
        $this->account->setAccountNumber('ACC001');
        $this->account->setCurrency('USD');
        $this->account->setBalance(1000);
        $this->account->setCreatedAt(new \DateTimeImmutable('2023-01-01'));
        $this->account->setUpdatedAt(new \DateTimeImmutable('2023-01-02'));
        
        $this->assertEquals(1, $this->account->getId());
        $this->assertEquals($client, $this->account->getClient());
        $this->assertEquals('ACC001', $this->account->getAccountNumber());
        $this->assertEquals('USD', $this->account->getCurrency());
        $this->assertEquals(1000, $this->account->getBalance());
        $this->assertEquals('2023-01-01', $this->account->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('2023-01-02', $this->account->getUpdatedAt()->format('Y-m-d'));
    }
    
    public function testAddTransaction(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);
        
        $this->account->addTransaction($transaction);
        
        $this->assertCount(1, $this->account->getTransactions());
        $this->assertSame($this->account, $transaction->getAccount());
    }
    
    public function testRemoveTransaction(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);
        
        $this->account->addTransaction($transaction);
        $this->assertCount(1, $this->account->getTransactions());
        
        $this->account->removeTransaction($transaction);
        $this->assertCount(0, $this->account->getTransactions());
    }
    
    public function testAddSourceTransaction(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);
        
        $this->account->addSourceTransaction($transaction);
        
        $this->assertCount(1, $this->account->getSourceTransactions());
        $this->assertSame($this->account, $transaction->getSourceAccount());
    }
    
    public function testRemoveSourceTransaction(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);
        
        $this->account->addSourceTransaction($transaction);
        $this->assertCount(1, $this->account->getSourceTransactions());
        
        $this->account->removeSourceTransaction($transaction);
        $this->assertCount(0, $this->account->getSourceTransactions());
    }
    
    public function testAddDestinationTransaction(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);
        
        $this->account->addDestinationTransaction($transaction);
        
        $this->assertCount(1, $this->account->getDestinationTransactions());
        $this->assertSame($this->account, $transaction->getDestinationAccount());
    }
    
    public function testRemoveDestinationTransaction(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);
        
        $this->account->addDestinationTransaction($transaction);
        $this->assertCount(1, $this->account->getDestinationTransactions());
        
        $this->account->removeDestinationTransaction($transaction);
        $this->assertCount(0, $this->account->getDestinationTransactions());
    }
}
