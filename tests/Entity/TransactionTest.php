<?php

namespace App\Tests\Entity;

use App\Entity\Account;
use App\Entity\Transaction;
use PHPUnit\Framework\TestCase;

class TransactionTest extends TestCase
{
    private Transaction $transaction;
    
    protected function setUp(): void
    {
        $this->transaction = new Transaction();
    }
    
    public function testGettersAndSetters(): void
    {
        $sourceAccount = new Account();
        $sourceAccount->setAccountNumber('ACC001');
        
        $destinationAccount = new Account();
        $destinationAccount->setAccountNumber('ACC002');
        
        $this->transaction->setId(1);
        $this->transaction->setSourceAccount($sourceAccount);
        $this->transaction->setDestinationAccount($destinationAccount);
        $this->transaction->setAmount(100);
        $this->transaction->setCurrency('USD');
        $this->transaction->setDescription('Test transaction');
        $this->transaction->setCreatedAt(new \DateTimeImmutable('2023-01-01'));
        
        $this->assertEquals(1, $this->transaction->getId());
        $this->assertEquals($sourceAccount, $this->transaction->getSourceAccount());
        $this->assertEquals($destinationAccount, $this->transaction->getDestinationAccount());
        $this->assertEquals(100, $this->transaction->getAmount());
        $this->assertEquals('USD', $this->transaction->getCurrency());
        $this->assertEquals('Test transaction', $this->transaction->getDescription());
        $this->assertEquals('2023-01-01', $this->transaction->getCreatedAt()->format('Y-m-d'));
    }
    
    public function testSetAccount(): void
    {
        $account = new Account();
        $account->setAccountNumber('ACC001');
        
        $this->transaction->setAccount($account);
        
        $this->assertEquals($account, $this->transaction->getAccount());
    }
}
