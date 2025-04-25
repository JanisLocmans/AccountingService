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

        $this->assertEquals(1, $this->account->getId());
        $this->assertEquals($client, $this->account->getClient());
        $this->assertEquals('ACC001', $this->account->getAccountNumber());
        $this->assertEquals('USD', $this->account->getCurrency());
        $this->assertEquals(1000, $this->account->getBalance());
    }

    public function testOutgoingTransactions(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);

        $this->account->addOutgoingTransaction($transaction);
        $this->assertCount(1, $this->account->getOutgoingTransactions());
        $this->assertSame($this->account, $transaction->getSourceAccount());

        $this->account->removeOutgoingTransaction($transaction);
        $this->assertCount(0, $this->account->getOutgoingTransactions());
        $this->assertNull($transaction->getSourceAccount());
    }

    public function testIncomingTransactions(): void
    {
        $transaction = new Transaction();
        $transaction->setAmount(100);

        $this->account->addIncomingTransaction($transaction);
        $this->assertCount(1, $this->account->getIncomingTransactions());
        $this->assertSame($this->account, $transaction->getDestinationAccount());

        $this->account->removeIncomingTransaction($transaction);
        $this->assertCount(0, $this->account->getIncomingTransactions());
        $this->assertNull($transaction->getDestinationAccount());
    }

    public function testGetAllTransactions(): void
    {
        $outgoingTransaction = new Transaction();
        $outgoingTransaction->setAmount(100);
        $outgoingTransaction->setCreatedAt(new \DateTimeImmutable());

        $incomingTransaction = new Transaction();
        $incomingTransaction->setAmount(200);
        $incomingTransaction->setCreatedAt(new \DateTimeImmutable());

        $this->account->addOutgoingTransaction($outgoingTransaction);
        $this->account->addIncomingTransaction($incomingTransaction);

        $allTransactions = $this->account->getAllTransactions();
        $this->assertCount(2, $allTransactions);
        $this->assertContains($outgoingTransaction, $allTransactions);
        $this->assertContains($incomingTransaction, $allTransactions);
    }
}
