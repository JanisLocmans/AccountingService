<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Repository\AccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for handling fund transfers between accounts.
 *
 * This service provides methods for transferring funds between accounts,
 * handling currency conversion when necessary.
 */
class TransferService implements TransferServiceInterface
{
    /**
     * TransferService constructor.
     *
     * @param EntityManagerInterface $entityManager Entity manager for database operations
     * @param AccountRepository $accountRepository Repository for account entities
     * @param CurrencyExchangeServiceInterface $currencyExchangeService Service for currency exchange operations
     * @param LoggerInterface|null $logger Logger for recording transfer operations
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountRepository $accountRepository,
        private CurrencyExchangeServiceInterface $currencyExchangeService,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Transfer funds between accounts.
     *
     * @param int $sourceAccountId ID of the source account
     * @param int $destinationAccountId ID of the destination account
     * @param float $amount Amount to transfer
     * @param string $currency Currency of the transfer
     * @param string|null $description Optional description of the transfer
     * @return Transaction The created transaction
     *
     * @throws \InvalidArgumentException If the accounts are not found or the transfer is invalid
     * @throws \RuntimeException If the transfer fails due to a system error
     */
    public function transfer(
        int $sourceAccountId,
        int $destinationAccountId,
        float $amount,
        string $currency,
        ?string $description = null
    ): Transaction {
        if ($this->logger) {
            $this->logger->info('Starting transfer', [
                'source_account_id' => $sourceAccountId,
                'destination_account_id' => $destinationAccountId,
                'amount' => $amount,
                'currency' => $currency,
            ]);
        }

        // Validate amount
        if ($amount <= 0) {
            $errorMessage = 'Amount must be positive';
            if ($this->logger) {
                $this->logger->error($errorMessage, [
                    'amount' => $amount,
                ]);
            }
            throw new \InvalidArgumentException($errorMessage);
        }

        // Find accounts
        $sourceAccount = $this->accountRepository->find($sourceAccountId);
        if (!$sourceAccount) {
            throw new \InvalidArgumentException('Source account not found');
        }

        $destinationAccount = $this->accountRepository->find($destinationAccountId);
        if (!$destinationAccount) {
            throw new \InvalidArgumentException('Destination account not found');
        }

        // Validate currency
        $this->validateCurrency($sourceAccount, $destinationAccount, $currency);

        // Calculate exchange rate and amounts
        $exchangeRate = $this->currencyExchangeService->getExchangeRate(
            $sourceAccount->getCurrency(),
            $destinationAccount->getCurrency()
        );
        $sourceAmount = $this->calculateSourceAmount(
            $amount,
            $currency,
            $sourceAccount->getCurrency()
        );
        $destinationAmount = $this->calculateDestinationAmount(
            $amount,
            $currency,
            $destinationAccount->getCurrency()
        );

        // Check if source account has sufficient funds
        if ($sourceAccount->getBalance() < $sourceAmount) {
            throw new \InvalidArgumentException('Insufficient funds in source account');
        }

        // Begin transaction
        $this->entityManager->beginTransaction();

        try {
            // Update account balances
            $sourceAccount->setBalance($sourceAccount->getBalance() - $sourceAmount);
            $destinationAccount->setBalance($destinationAccount->getBalance() + $destinationAmount);

            // Create transaction record
            $transaction = new Transaction();
            $transaction->setSourceAccount($sourceAccount);
            $transaction->setDestinationAccount($destinationAccount);
            $transaction->setAmount($amount);
            $transaction->setCurrency($currency);
            $transaction->setDescription($description);
            $transaction->setExchangeRate($exchangeRate);

            // Save changes
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
            $this->entityManager->commit();

            if ($this->logger) {
                $this->logger->info('Transfer completed successfully', [
                    'transaction_id' => $transaction->getId(),
                    'source_account' => $sourceAccount->getAccountNumber(),
                    'destination_account' => $destinationAccount->getAccountNumber(),
                    'amount' => $amount,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                ]);
            }

            return $transaction;
        } catch (\Exception $e) {
            $this->entityManager->rollback();

            if ($this->logger) {
                $this->logger->error('Transfer failed', [
                    'source_account_id' => $sourceAccountId,
                    'destination_account_id' => $destinationAccountId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'error' => $e->getMessage(),
                    'exception' => get_class($e),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Validate that the currency is valid for the transfer
     */
    private function validateCurrency(Account $sourceAccount, Account $destinationAccount, string $currency): void
    {
        // Check if the currency is supported
        if (!$this->currencyExchangeService->isCurrencySupported($currency)) {
            throw new \InvalidArgumentException(
                "Currency {$currency} is not supported. Supported currencies: " .
                implode(', ', ['USD', 'EUR', 'GBP'])
            );
        }

        // If currency matches destination account currency, it's valid
        if ($currency === $destinationAccount->getCurrency()) {
            return;
        }

        // If currency matches source account currency, it's valid
        if ($currency === $sourceAccount->getCurrency()) {
            return;
        }

        // Otherwise, it's invalid
        throw new \InvalidArgumentException(
            "Currency of funds in transfer operation must match either " .
            "source account currency ({$sourceAccount->getCurrency()}) or " .
            "destination account currency ({$destinationAccount->getCurrency()})"
        );
    }

    /**
     * Calculate the amount to deduct from source account
     */
    private function calculateSourceAmount(
        float $amount,
        string $transferCurrency,
        string $sourceAccountCurrency
    ): float {
        if ($transferCurrency === $sourceAccountCurrency) {
            return $amount;
        }

        // Convert from transfer currency to source account currency
        $rate = $this->currencyExchangeService->getExchangeRate($transferCurrency, $sourceAccountCurrency);
        return $amount * $rate;
    }

    /**
     * Calculate the amount to add to destination account
     */
    private function calculateDestinationAmount(
        float $amount,
        string $transferCurrency,
        string $destinationAccountCurrency
    ): float {
        if ($transferCurrency === $destinationAccountCurrency) {
            return $amount;
        }

        // Convert from transfer currency to destination account currency
        $rate = $this->currencyExchangeService->getExchangeRate($transferCurrency, $destinationAccountCurrency);
        return $amount * $rate;
    }
}
