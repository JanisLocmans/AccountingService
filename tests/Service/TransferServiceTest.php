<?php

namespace App\Tests\Service;

use App\Entity\Account;
use App\Entity\Client;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use App\Service\CurrencyExchangeService;
use App\Service\TransferService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TransferServiceTest extends TestCase
{
    private $entityManager;
    private $accountRepository;
    private $currencyExchangeService;
    private $transferService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->currencyExchangeService = $this->createMock(CurrencyExchangeService::class);

        $this->transferService = new TransferService(
            $this->entityManager,
            $this->accountRepository,
            $this->currencyExchangeService
        );
    }

    public function testTransferWithSameCurrency(): void
    {
        // Create test accounts
        $client = new Client();
        $client->setName('Test Client');
        $client->setEmail('test@example.com');

        $sourceAccount = new Account();
        $sourceAccount->setId(1);
        $sourceAccount->setClient($client);
        $sourceAccount->setAccountNumber('ACC001');
        $sourceAccount->setCurrency('USD');
        $sourceAccount->setBalance(1000);

        $destinationAccount = new Account();
        $destinationAccount->setId(2);
        $destinationAccount->setClient($client);
        $destinationAccount->setAccountNumber('ACC002');
        $destinationAccount->setCurrency('USD');
        $destinationAccount->setBalance(500);

        // Configure mocks
        $this->accountRepository->method('find')
            ->willReturnMap([
                [1, $sourceAccount],
                [2, $destinationAccount]
            ]);

        $this->currencyExchangeService->method('getExchangeRate')
            ->with('USD', 'USD')
            ->willReturn(1.0);

        $this->currencyExchangeService->method('isCurrencySupported')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function ($transaction) {
                return $transaction instanceof Transaction
                    && $transaction->getSourceAccount()->getId() === 1
                    && $transaction->getDestinationAccount()->getId() === 2
                    && $transaction->getAmount() === 100.0
                    && $transaction->getCurrency() === 'USD'
                    && $transaction->getExchangeRate() === 1.0;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('commit');

        // Execute transfer
        $transaction = $this->transferService->transfer(1, 2, 100, 'USD', 'Test transfer');

        // Verify results
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(900, $sourceAccount->getBalance());
        $this->assertEquals(600, $destinationAccount->getBalance());
    }

    public function testTransferWithDifferentCurrencies(): void
    {
        // Create test accounts
        $client = new Client();
        $client->setName('Test Client');
        $client->setEmail('test@example.com');

        $sourceAccount = new Account();
        $sourceAccount->setId(1);
        $sourceAccount->setClient($client);
        $sourceAccount->setAccountNumber('ACC001');
        $sourceAccount->setCurrency('USD');
        $sourceAccount->setBalance(1000);

        $destinationAccount = new Account();
        $destinationAccount->setId(2);
        $destinationAccount->setClient($client);
        $destinationAccount->setAccountNumber('ACC002');
        $destinationAccount->setCurrency('EUR');
        $destinationAccount->setBalance(500);

        // Configure mocks
        $this->accountRepository->method('find')
            ->willReturnMap([
                [1, $sourceAccount],
                [2, $destinationAccount]
            ]);

        $this->currencyExchangeService->method('getExchangeRate')
            ->willReturnMap([
                ['USD', 'EUR', 0.85],
                ['EUR', 'USD', 1.18],
                ['EUR', 'EUR', 1.0]
            ]);

        $this->currencyExchangeService->method('isCurrencySupported')
            ->willReturn(true);

        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManager->expects($this->once())
            ->method('commit');

        // Execute transfer (100 EUR)
        $transaction = $this->transferService->transfer(1, 2, 100, 'EUR', 'Test transfer');

        // Verify results
        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(882, $sourceAccount->getBalance()); // 1000 - (100 * 1.18)
        $this->assertEquals(600, $destinationAccount->getBalance()); // 500 + 100
    }

    public function testTransferWithInsufficientFunds(): void
    {
        // Create test accounts
        $client = new Client();
        $client->setName('Test Client');
        $client->setEmail('test@example.com');

        $sourceAccount = new Account();
        $sourceAccount->setId(1);
        $sourceAccount->setClient($client);
        $sourceAccount->setAccountNumber('ACC001');
        $sourceAccount->setCurrency('USD');
        $sourceAccount->setBalance(50);

        $destinationAccount = new Account();
        $destinationAccount->setId(2);
        $destinationAccount->setClient($client);
        $destinationAccount->setAccountNumber('ACC002');
        $destinationAccount->setCurrency('USD');
        $destinationAccount->setBalance(500);

        // Configure mocks
        $this->accountRepository->method('find')
            ->willReturnMap([
                [1, $sourceAccount],
                [2, $destinationAccount]
            ]);

        $this->currencyExchangeService->method('getExchangeRate')
            ->with('USD', 'USD')
            ->willReturn(1.0);

        $this->currencyExchangeService->method('isCurrencySupported')
            ->willReturn(true);

        // For this test, we'll use the real TransferService
        // The balance is set to 50 and we're trying to transfer 100, so it should fail

        // Expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient funds in source account');

        // Execute transfer
        $this->transferService->transfer(1, 2, 100, 'USD', 'Test transfer');
    }

    public function testTransferWithInvalidCurrency(): void
    {
        // Create test accounts
        $client = new Client();
        $client->setName('Test Client');
        $client->setEmail('test@example.com');

        $sourceAccount = new Account();
        $sourceAccount->setId(1);
        $sourceAccount->setClient($client);
        $sourceAccount->setAccountNumber('ACC001');
        $sourceAccount->setCurrency('USD');
        $sourceAccount->setBalance(1000);

        $destinationAccount = new Account();
        $destinationAccount->setId(2);
        $destinationAccount->setClient($client);
        $destinationAccount->setAccountNumber('ACC002');
        $destinationAccount->setCurrency('EUR');
        $destinationAccount->setBalance(500);

        // Configure mocks
        $this->accountRepository->method('find')
            ->willReturnMap([
                [1, $sourceAccount],
                [2, $destinationAccount]
            ]);

        // For this test, we want to simulate that GBP is supported but doesn't match either account
        $this->currencyExchangeService->method('isCurrencySupported')
            ->willReturn(true);

        // For this test, we'll configure the currencyExchangeService to make the validation fail
        $this->currencyExchangeService = $this->createMock(CurrencyExchangeService::class);
        $this->currencyExchangeService->method('isCurrencySupported')
            ->willReturn(true);

        // Create a new TransferService with our mocked dependencies
        $transferService = new TransferService(
            $this->entityManager,
            $this->accountRepository,
            $this->currencyExchangeService
        );

        // Expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Currency of funds in transfer operation must match');

        // Execute transfer with invalid currency (GBP)
        $transferService->transfer(1, 2, 100, 'GBP', 'Test transfer');
    }

    public function testTransferWithNonExistentSourceAccount(): void
    {
        // Configure mocks to return null for source account
        $this->accountRepository->method('find')
            ->willReturnCallback(function($id) {
                return $id === 1 ? null : new Account();
            });

        // Expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source account not found');

        // Execute transfer with non-existent source account
        $this->transferService->transfer(1, 2, 100, 'USD', 'Test transfer');
    }

    public function testTransferWithNonExistentDestinationAccount(): void
    {
        // Create source account
        $sourceAccount = new Account();
        $sourceAccount->setId(1);
        $sourceAccount->setCurrency('USD');
        $sourceAccount->setBalance(1000);

        // Configure mocks
        $this->accountRepository->method('find')
            ->willReturnCallback(function($id) use ($sourceAccount) {
                return $id === 1 ? $sourceAccount : null;
            });

        // Expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Destination account not found');

        // Execute transfer with non-existent destination account
        $this->transferService->transfer(1, 2, 100, 'USD', 'Test transfer');
    }

    public function testCalculateSourceAmount(): void
    {
        // Create test accounts
        $client = new Client();

        $sourceAccount = new Account();
        $sourceAccount->setId(1);
        $sourceAccount->setClient($client);
        $sourceAccount->setCurrency('USD');
        $sourceAccount->setBalance(1000);

        $destinationAccount = new Account();
        $destinationAccount->setId(2);
        $destinationAccount->setClient($client);
        $destinationAccount->setCurrency('EUR');
        $destinationAccount->setBalance(500);

        // Configure mocks
        $this->accountRepository->method('find')
            ->willReturnMap([
                [1, $sourceAccount],
                [2, $destinationAccount]
            ]);

        $this->currencyExchangeService->method('getExchangeRate')
            ->willReturnMap([
                ['USD', 'EUR', 0.85],
                ['EUR', 'USD', 1.18]
            ]);

        $this->currencyExchangeService->method('isCurrencySupported')
            ->willReturn(true);

        // Test with source currency
        $transaction1 = $this->transferService->transfer(1, 2, 100, 'USD', 'Test USD transfer');
        $this->assertEquals(100, $transaction1->getAmount());
        $this->assertEquals('USD', $transaction1->getCurrency());
        $this->assertEquals(900, $sourceAccount->getBalance()); // 1000 - 100

        // Reset balance
        $sourceAccount->setBalance(1000);

        // Test with destination currency
        $transaction2 = $this->transferService->transfer(1, 2, 100, 'EUR', 'Test EUR transfer');
        $this->assertEquals(100, $transaction2->getAmount());
        $this->assertEquals('EUR', $transaction2->getCurrency());
        $this->assertEquals(882, $sourceAccount->getBalance()); // 1000 - (100 * 1.18)
    }
}
