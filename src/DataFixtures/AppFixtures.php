<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Client;
use App\Entity\Transaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create clients
        $client1 = new Client();
        $client1->setName('TestUser2');
        $client1->setEmail('TestUser2@example.com');
        $manager->persist($client1);

        $client2 = new Client();
        $client2->setName('TestUser1');
        $client2->setEmail('TestUser1@example.com');
        $manager->persist($client2);

        // Create accounts for client 1
        $account1 = new Account();
        $account1->setClient($client1);
        $account1->setAccountNumber('ACC001');
        $account1->setCurrency('USD');
        $account1->setBalance(1000);
        $manager->persist($account1);

        $account2 = new Account();
        $account2->setClient($client1);
        $account2->setAccountNumber('ACC002');
        $account2->setCurrency('EUR');
        $account2->setBalance(500);
        $manager->persist($account2);

        // Create accounts for client 2
        $account3 = new Account();
        $account3->setClient($client2);
        $account3->setAccountNumber('ACC003');
        $account3->setCurrency('GBP');
        $account3->setBalance(750);
        $manager->persist($account3);

        // Create some transactions
        $transaction1 = new Transaction();
        $transaction1->setSourceAccount($account1);
        $transaction1->setDestinationAccount($account2);
        $transaction1->setAmount(100);
        $transaction1->setCurrency('USD');
        $transaction1->setExchangeRate(0.85);
        $transaction1->setDescription('Test transaction 1');
        $transaction1->setCreatedAt(new \DateTimeImmutable('-1 day'));
        $manager->persist($transaction1);

        $transaction2 = new Transaction();
        $transaction2->setSourceAccount($account2);
        $transaction2->setDestinationAccount($account3);
        $transaction2->setAmount(50);
        $transaction2->setCurrency('EUR');
        $transaction2->setExchangeRate(0.9);
        $transaction2->setDescription('Test transaction 2');
        $transaction2->setCreatedAt(new \DateTimeImmutable('-2 days'));
        $manager->persist($transaction2);

        $transaction3 = new Transaction();
        $transaction3->setSourceAccount($account3);
        $transaction3->setDestinationAccount($account1);
        $transaction3->setAmount(75);
        $transaction3->setCurrency('GBP');
        $transaction3->setExchangeRate(1.3);
        $transaction3->setDescription('Test transaction 3');
        $transaction3->setCreatedAt(new \DateTimeImmutable('-3 days'));
        $manager->persist($transaction3);

        $manager->flush();
    }
}
